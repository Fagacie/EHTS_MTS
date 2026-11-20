<?php

namespace Tests\Feature;

use App\Enums\AttendanceReviewStatus;
use App\Enums\AttendanceStatus;
use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use App\Jobs\ProcessVideoToHlsJob;
use App\Models\Attendance;
use App\Models\Booking;
use App\Models\InstructorProfile;
use App\Models\Module;
use App\Models\TrainingSession;
use App\Models\User;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\CertificateSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SessionAndBookingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Stage5InstructorPortalInertiaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleAndPermissionSeeder::class,
            DemoUserSeeder::class,
            CatalogueSeeder::class,
            SessionAndBookingSeeder::class,
            CertificateSeeder::class,
        ]);
    }

    public function test_all_eight_instructor_routes_render_expected_inertia_components_and_leak_no_sensitive_data(): void
    {
        $instructor = User::query()->where('email', 'instructor@ecohydrotech.com.my')->firstOrFail();
        $instructor->forceFill(['two_factor_secret' => 'SUPER_SECRET_TOTP_KEY'])->save();

        $session = $instructor->assignedSessions()->firstOrFail();

        // 1. Dashboard
        $this->actingAs($instructor)
            ->get(route('instructor.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Instructor/Dashboard')
                ->has('instructor.displayName')
                ->has('kpis.assignedSessionsCount')
                ->has('assignedSessions')
                ->missing('auth.user.two_factor_secret')
            );

        // 2. Sessions Index
        $this->actingAs($instructor)
            ->get(route('instructor.sessions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Instructor/Sessions/Index')
                ->has('sessions')
                ->has('statusFilter')
            );

        // 3. Session Show / Roster
        $this->actingAs($instructor)
            ->get(route('instructor.sessions.show', $session))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Instructor/Sessions/Show')
                ->has('session.sessionCode')
                ->has('roster')
            );

        // 4. Attendance Marking Sheet
        $this->actingAs($instructor)
            ->get(route('instructor.sessions.attendance', $session))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Instructor/Sessions/Attendance')
                ->has('session.sessionCode')
                ->has('attendances')
                ->has('allMarked')
                ->has('isLockedVerified')
            );

        // 5. Completion Report
        $this->actingAs($instructor)
            ->get(route('instructor.sessions.completion', $session))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Instructor/Sessions/Completion')
                ->has('session.sessionCode')
                ->has('submitUrl')
            );

        // 6. Recordings
        $this->actingAs($instructor)
            ->get(route('instructor.sessions.recordings', $session))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Instructor/Sessions/Recordings')
                ->has('session.sessionCode')
                ->has('recordings')
                ->has('submitUrl')
            );

        // 7. Profile Edit
        $this->actingAs($instructor)
            ->get(route('instructor.profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Instructor/Profile')
                ->has('profile.displayName')
                ->has('profile.isPublic')
                ->has('updateUrl')
            );

        // 8. Notifications
        $this->actingAs($instructor)
            ->get(route('instructor.notifications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Instructor/Notifications/Index')
                ->has('filter')
                ->has('unreadCount')
                ->has('notifications')
                ->has('pagination')
            );
    }

    public function test_unauthenticated_and_unauthorized_users_are_guarded(): void
    {
        $session = TrainingSession::query()->firstOrFail();

        // Guest redirected to login
        $this->get(route('instructor.dashboard'))
            ->assertRedirect(route('login'));

        $this->get(route('instructor.sessions.show', $session))
            ->assertRedirect(route('login'));

        // Customer without instructor role gets 403
        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();

        $this->actingAs($trainee)
            ->get(route('instructor.dashboard'))
            ->assertForbidden();

        $this->actingAs($trainee)
            ->get(route('instructor.sessions.show', $session))
            ->assertForbidden();
    }

    public function test_instructor_cannot_access_unassigned_session(): void
    {
        $instructor = User::query()->where('email', 'instructor@ecohydrotech.com.my')->firstOrFail();

        $existing = TrainingSession::query()->firstOrFail();
        $unassignedSession = $existing->replicate();
        $unassignedSession->session_code = 'SES-UNASSIGNED-999';
        $unassignedSession->slug = 'ses-unassigned-999';
        $unassignedSession->title = 'Unassigned Cohort';
        $unassignedSession->save();

        $this->actingAs($instructor)
            ->get(route('instructor.sessions.show', $unassignedSession))
            ->assertForbidden();

        $this->actingAs($instructor)
            ->get(route('instructor.sessions.attendance', $unassignedSession))
            ->assertForbidden();
    }

    public function test_two_step_attendance_draft_and_final_submission_workflow(): void
    {
        $instructor = User::query()->where('email', 'instructor@ecohydrotech.com.my')->firstOrFail();
        $session = $instructor->assignedSessions()->firstOrFail();

        // Ensure attendance rows are synchronized
        $this->actingAs($instructor)->get(route('instructor.sessions.attendance', $session));

        $attendances = Attendance::query()->where('training_session_id', $session->id)->get();
        $this->assertNotEmpty($attendances);

        // 1. Save draft with Present marks
        $records = [];
        foreach ($attendances as $attendance) {
            $records[$attendance->id] = [
                'attendance_status' => 'present',
                'remarks' => 'Present for full physical cohort',
            ];
        }

        $response = $this->actingAs($instructor)->put(
            route('instructor.sessions.attendance.update', $session),
            ['records' => $records]
        );

        $response->assertRedirect(route('instructor.sessions.attendance', $session));
        $response->assertSessionHas('status', 'Attendance draft saved successfully.');

        foreach ($attendances as $attendance) {
            $this->assertDatabaseHas('attendance', [
                'id' => $attendance->id,
                'attendance_status' => AttendanceStatus::Present->value,
                'remarks' => 'Present for full physical cohort',
            ]);
        }

        // 2. Submit attendance for verification
        $submitResponse = $this->actingAs($instructor)->post(
            route('instructor.sessions.attendance.submit', $session)
        );

        $submitResponse->assertRedirect(route('instructor.sessions.attendance', $session));
        $submitResponse->assertSessionHas('status', 'Session attendance submitted for administrator verification.');

        foreach ($attendances as $attendance) {
            $this->assertDatabaseHas('attendance', [
                'id' => $attendance->id,
                'review_status' => AttendanceReviewStatus::Submitted->value,
            ]);
        }
    }

    public function test_attendance_submission_fails_if_any_participant_is_pending(): void
    {
        $instructor = User::query()->where('email', 'instructor@ecohydrotech.com.my')->firstOrFail();
        $session = $instructor->assignedSessions()->firstOrFail();

        $this->actingAs($instructor)->get(route('instructor.sessions.attendance', $session));

        $attendances = Attendance::query()->where('training_session_id', $session->id)->get();
        $this->assertNotEmpty($attendances);

        // Reset one attendance to pending
        $first = $attendances->first();
        $first->update(['attendance_status' => AttendanceStatus::Pending]);

        $response = $this->actingAs($instructor)->post(
            route('instructor.sessions.attendance.submit', $session)
        );

        $response->assertRedirect(route('instructor.sessions.attendance', $session));
        $response->assertSessionHas('error');
    }

    public function test_instructor_can_submit_session_completion_report(): void
    {
        $instructor = User::query()->where('email', 'instructor@ecohydrotech.com.my')->firstOrFail();
        $session = $instructor->assignedSessions()->firstOrFail();

        $notes = 'All hydraulic calibration labs completed successfully. All trainees passed practical exercises.';

        $response = $this->actingAs($instructor)->post(
            route('instructor.sessions.completion.store', $session),
            ['completion_notes' => $notes]
        );

        $response->assertRedirect(route('instructor.sessions.show', $session));
        $response->assertSessionHas('status', 'Session completion report saved and submitted.');

        $this->assertDatabaseHas('training_sessions', [
            'id' => $session->id,
            'completion_notes' => $notes,
            'completion_submitted_by' => $instructor->id,
        ]);
    }

    public function test_instructor_can_upload_session_recording(): void
    {
        Storage::fake('recordings');
        Queue::fake([ProcessVideoToHlsJob::class]);

        $instructor = User::query()->where('email', 'instructor@ecohydrotech.com.my')->firstOrFail();
        $session = $instructor->assignedSessions()->firstOrFail();

        $fakeFile = UploadedFile::fake()->create('lecture-part-1.mp4', 1024, 'video/mp4');

        $response = $this->actingAs($instructor)->post(
            route('instructor.sessions.recordings.store', $session),
            [
                'title' => 'Part 1: Hydrodynamic Analysis Lecture',
                'video_file' => $fakeFile,
                'provider_video_id' => 'rec-test-001',
                'duration_seconds' => 3600,
                'description' => 'Morning session covering channel flow calibration',
            ]
        );

        $response->assertRedirect(route('instructor.sessions.recordings', $session));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('session_recordings', [
            'training_session_id' => $session->id,
            'title' => 'Part 1: Hydrodynamic Analysis Lecture',
            'provider_video_id' => 'rec-test-001',
        ]);

        Queue::assertPushed(ProcessVideoToHlsJob::class);
    }

    public function test_instructor_can_update_profile(): void
    {
        $instructor = User::query()->where('email', 'instructor@ecohydrotech.com.my')->firstOrFail();

        $response = $this->actingAs($instructor)->put(
            route('instructor.profile.update'),
            [
                'display_name' => 'Ir. Dr. Ahmad Razif, P.Eng',
                'professional_title' => 'Senior Water Resources Modeller',
                'qualifications' => 'PhD in Hydrology, BEM Registered P.Eng',
                'short_bio' => '20+ years flood modelling and stormwater engineering specialist.',
                'specialisations_text' => 'HEC-RAS, MSMA, Flood Forecasting, SWMM',
                'biography' => 'Full detailed biography covering consulting projects across Southeast Asia.',
            ]
        );

        $response->assertRedirect(route('instructor.profile.edit'));
        $response->assertSessionHas('status', 'Instructor profile updated successfully.');

        $this->assertDatabaseHas('instructor_profiles', [
            'user_id' => $instructor->id,
            'display_name' => 'Ir. Dr. Ahmad Razif, P.Eng',
            'professional_title' => 'Senior Water Resources Modeller',
            'qualifications' => 'PhD in Hydrology, BEM Registered P.Eng',
        ]);
    }

    public function test_instructor_can_mark_all_notifications_read(): void
    {
        $instructor = User::query()->where('email', 'instructor@ecohydrotech.com.my')->firstOrFail();

        $response = $this->actingAs($instructor)->post(
            route('instructor.notifications.read-all')
        );

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertSame(0, $instructor->unreadNotifications()->count());
    }
}
