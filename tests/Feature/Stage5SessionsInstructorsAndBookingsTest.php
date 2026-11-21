<?php

namespace Tests\Feature;

use App\Enums\AttendanceReviewStatus;
use App\Enums\AttendanceStatus;
use App\Enums\BookingStatus;
use App\Enums\OrderStatus;
use App\Enums\RecordingAccessPolicy;
use App\Enums\RecordingStatus;
use App\Enums\RoleSlug;
use App\Enums\SessionStatus;
use App\Models\Attendance;
use App\Models\Booking;
use App\Models\InstructorProfile;
use App\Models\Module;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\Booking\SessionBookingService;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SessionAndBookingSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Stage5SessionsInstructorsAndBookingsTest extends TestCase
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
        ]);
    }

    public function test_public_instructor_directory_and_profiles_respect_publication_controls(): void
    {
        $publicProfile = InstructorProfile::query()->where('is_public', true)->firstOrFail();

        $this->get(route('instructors.index'))
            ->assertOk()
            ->assertSee($publicProfile->display_name)
            ->assertSee('Dr. Amirul Hakim');

        $this->get(route('instructors.show', $publicProfile))
            ->assertOk()
            ->assertSee($publicProfile->display_name)
            ->assertSee('SES-2026-001');

        // Hide the profile and verify it disappears from public directory and returns 404
        $publicProfile->update(['is_public' => false]);

        $this->get(route('instructors.index'))
            ->assertOk()
            ->assertDontSee($publicProfile->display_name);

        $this->get(route('instructors.show', $publicProfile))
            ->assertNotFound();
    }

    public function test_public_sessions_listing_detail_and_module_detail_display_upcoming_sessions(): void
    {
        $session = TrainingSession::query()->where('session_code', 'SES-2026-001')->firstOrFail();

        $this->get(route('sessions.index'))
            ->assertOk()
            ->assertSee('SES-2026-001')
            ->assertSee('SES-2026-002')
            ->assertSee('SES-2026-003');

        // Filter by category
        $this->get(route('sessions.index', ['category' => 'river-basin-flood-modelling']))
            ->assertOk()
            ->assertSee('SES-2026-002')
            ->assertDontSee('SES-2026-001');

        // Session detail page shows venue, price, capacity and assigned instructors
        $this->get(route('sessions.show', $session))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Sessions/Show')
                ->where('session.sessionCode', 'SES-2026-001')
                ->where('session.venueName', 'Ecohydrotech Training Centre, Hall A')
                ->where('session.priceLabel', 'RM 890.00')
                ->where('session.seatsOpenLabel', '25 / 25 seats open')
                ->where('session.instructors.0.displayName', 'Ir. Siti Norzahra, P.Eng')
                ->where('session.instructors.1.displayName', 'Dr. Amirul Hakim'),
            );

        // Module detail page shows upcoming physical sessions for that module
        $this->get(route('catalogue.modules.show', $session->module))
            ->assertOk()
            ->assertSee('SES-2026-001')
            ->assertSee('MSMA 2nd Edition Practical Stormwater Design Workshop');
    }

    public function test_customer_booking_reserves_seat_for_15_minutes_prevents_overbooking_and_releases_expired_holds(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $module = Module::query()->where('code', 'EHT-HYD-101')->firstOrFail();

        // Create a 1-seat physical session to test capacity & concurrency boundaries
        $singleSeatSession = TrainingSession::query()->create([
            'module_id' => $module->id,
            'session_code' => 'SES-2026-CAP1',
            'title' => 'Single-Seat Flood Calibration Clinic',
            'slug' => 'single-seat-flood-calibration-clinic',
            'description' => '1-seat test session.',
            'starts_at' => now()->addDays(10)->setTime(9, 0),
            'ends_at' => now()->addDays(10)->setTime(17, 0),
            'duration_minutes' => 480,
            'timezone' => 'Asia/Kuala_Lumpur',
            'venue_name' => 'Lab B',
            'venue_address' => 'Jalan Ilmu 1',
            'venue_city' => 'Shah Alam',
            'venue_state' => 'Selangor',
            'capacity' => 1,
            'reserved_seats_count' => 0,
            'confirmed_seats_count' => 0,
            'price' => '600.00',
            'currency' => 'MYR',
            'registration_opens_at' => now()->subDay(),
            'registration_closes_at' => now()->addDays(8)->setTime(17, 0),
            'recording_access_policy' => RecordingAccessPolicy::AttendeesOnly->value,
            'status' => SessionStatus::Published->value,
            'published_at' => now()->subDay(),
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $customer1 = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $customer2 = User::factory()->create(['email_verified_at' => now()]);
        $customer2->assignRole(RoleSlug::Customer);

        // Customer 1 reserves the only seat
        $response = $this->actingAs($customer1)
            ->post(route('checkout.session.store', $singleSeatSession), [
                'customer_type' => 'company_sponsored',
                'phone' => '+6019-245 7710',
                'company_name' => 'LembagaPerunding Hidro Sdn. Bhd.',
            ]);

        $booking1 = Booking::query()
            ->where('user_id', $customer1->id)
            ->where('training_session_id', $singleSeatSession->id)
            ->firstOrFail();

        $response->assertRedirect(route('portal.bookings.show', $booking1));

        $this->assertSame(BookingStatus::PendingPayment, $booking1->status);
        $this->assertSame(OrderStatus::PendingPayment, $booking1->order->status);
        $this->assertNotNull($booking1->reservation_expires_at);
        $this->assertTrue($booking1->reservation_expires_at->isFuture());

        $singleSeatSession->refresh();
        $this->assertSame(1, $singleSeatSession->reserved_seats_count);
        $this->assertSame(0, $singleSeatSession->confirmed_seats_count);
        $this->assertSame(SessionStatus::Full, $singleSeatSession->status);

        // Customer 2 tries to book while Customer 1's 15-minute hold is active -> rejected (overbooking prevented)
        $this->actingAs($customer2)
            ->from(route('checkout.session.show', $singleSeatSession))
            ->post(route('checkout.session.store', $singleSeatSession), [
                'customer_type' => 'individual',
                'phone' => '+6012-111 2222',
            ])
            ->assertRedirect(route('checkout.session.show', $singleSeatSession))
            ->assertSessionHas('error', 'This training session is currently full.');

        $this->assertDatabaseMissing('bookings', [
            'user_id' => $customer2->id,
            'training_session_id' => $singleSeatSession->id,
        ]);

        // Expire Customer 1's hold and run the scheduled reconciliation command
        $booking1->update(['reservation_expires_at' => now()->subMinute()]);

        Artisan::call('etms:reconcile-session-seats');

        $booking1->refresh();
        $singleSeatSession->refresh();

        $this->assertSame(BookingStatus::Expired, $booking1->status);
        $this->assertSame(OrderStatus::Expired, $booking1->order->fresh()->status);
        $this->assertSame(0, $singleSeatSession->reserved_seats_count);
        $this->assertSame(SessionStatus::Published, $singleSeatSession->status);

        // Customer 2 can now book the released seat
        $this->actingAs($customer2)
            ->post(route('checkout.session.store', $singleSeatSession), [
                'customer_type' => 'individual',
                'phone' => '+6012-111 2222',
            ]);

        $booking2 = Booking::query()
            ->where('user_id', $customer2->id)
            ->where('training_session_id', $singleSeatSession->id)
            ->firstOrFail();

        $this->assertSame(BookingStatus::PendingPayment, $booking2->status);
        $this->assertSame(1, $singleSeatSession->fresh()->reserved_seats_count);
    }

    public function test_booking_workflow_rejects_registration_after_closing_deadline(): void
    {
        $session = TrainingSession::query()->where('session_code', 'SES-2026-001')->firstOrFail();
        $session->update([
            'registration_opens_at' => now()->subDays(10),
            'registration_closes_at' => now()->subMinute(),
        ]);

        $customer = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();

        $this->actingAs($customer)
            ->from(route('checkout.session.show', $session))
            ->post(route('checkout.session.store', $session), [
                'customer_type' => 'individual',
            ])
            ->assertRedirect(route('checkout.session.show', $session))
            ->assertSessionHas('error', 'Registration for this training session has closed.');

        $this->assertSame(SessionStatus::RegistrationClosed, $session->fresh()->status);
    }

    public function test_instructor_portal_enforces_session_assignment_and_attendance_completion_and_recording_workflows(): void
    {
        $instructorSiti = User::query()->where('email', 'instructor@ecohydrotech.com.my')->firstOrFail();
        $instructorAmirul = User::query()->where('email', 'staff.trainee@ecohydrotech.com.my')->firstOrFail();

        $session3 = TrainingSession::query()->where('session_code', 'SES-2026-003')->firstOrFail();
        $session2 = TrainingSession::query()->where('session_code', 'SES-2026-002')->firstOrFail();
        $session3->update([
            'starts_at' => now()->subHours(2),
            'ends_at' => now()->addHours(4),
            'registration_closes_at' => now()->subHours(3),
            'status' => SessionStatus::InProgress,
        ]);
        $session3->refresh();

        // Dr. Amirul is NOT assigned to SES-2026-002 -> must receive 403 Forbidden
        $this->actingAs($instructorAmirul)
            ->get(route('instructor.sessions.show', $session2))
            ->assertForbidden();

        // Ir. Siti IS assigned to SES-2026-003 -> can view roster and commercial pricing is hidden
        $this->actingAs($instructorSiti)
            ->get(route('instructor.sessions.show', $session3))
            ->assertOk()
            ->assertSee('Ahmad Faiz')
            ->assertSee('BKG-2026-DEMO01')
            ->assertDontSee('ORD-2026-DEMO01');

        // Attempting to submit attendance while participant is still `pending` is blocked
        $this->actingAs($instructorSiti)
            ->post(route('instructor.sessions.attendance.submit', $session3))
            ->assertRedirect(route('instructor.sessions.attendance', $session3))
            ->assertSessionHas('error');

        $attendance = Attendance::query()->where('training_session_id', $session3->id)->firstOrFail();
        $this->assertSame(AttendanceReviewStatus::Draft, $attendance->review_status);

        // Mark participant as `present` and save draft
        $this->actingAs($instructorSiti)
            ->put(route('instructor.sessions.attendance.update', $session3), [
                'records' => [
                    $attendance->id => [
                        'attendance_status' => AttendanceStatus::Present->value,
                        'remarks' => 'Attended full day field clinic and completed calculation sheet.',
                    ],
                ],
            ])
            ->assertRedirect(route('instructor.sessions.attendance', $session3));

        $attendance->refresh();
        $this->assertSame(AttendanceStatus::Present, $attendance->attendance_status);
        $this->assertSame($instructorSiti->id, $attendance->recorded_by);

        // Now submit attendance for administrator verification
        $this->actingAs($instructorSiti)
            ->post(route('instructor.sessions.attendance.submit', $session3))
            ->assertRedirect(route('instructor.sessions.attendance', $session3));

        $attendance->refresh();
        $this->assertSame(AttendanceReviewStatus::Submitted, $attendance->review_status);
        $this->assertSame($instructorSiti->id, $attendance->submitted_by);
        $this->assertNotNull($attendance->submitted_at);

        // Submit session completion notes
        $this->actingAs($instructorSiti)
            ->post(route('instructor.sessions.completion.store', $session3), [
                'completion_notes' => 'All wetland sizing modules and bio-retention lab exercises completed on schedule.',
            ])
            ->assertRedirect(route('instructor.sessions.show', $session3));

        $this->assertSame(
            'All wetland sizing modules and bio-retention lab exercises completed on schedule.',
            $session3->fresh()->completion_notes,
        );

        // Submit session recording metadata / stream reference (creates pending_review recording on local storage)
        $this->actingAs($instructorSiti)
            ->post(route('instructor.sessions.recordings.store', $session3), [
                'title' => 'SES-2026-003 Full Clinic Recording',
                'description' => 'Recorded lecture and calculation walkthrough.',
                'provider_video_id' => 'local-vid-ses003-01',
                'duration_seconds' => 5400,
            ])
            ->assertRedirect(route('instructor.sessions.recordings', $session3));

        $this->assertDatabaseHas('session_recordings', [
            'training_session_id' => $session3->id,
            'provider' => 'local_hls',
            'provider_video_id' => 'local-vid-ses003-01',
            'status' => RecordingStatus::PendingReview->value,
            'submitted_by' => $instructorSiti->id,
        ]);
    }

    public function test_admin_session_cancellation_bulk_cancels_active_bookings_and_resets_seat_counters(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $session3 = TrainingSession::query()->where('session_code', 'SES-2026-003')->firstOrFail();
        $booking = Booking::query()->where('booking_reference', 'BKG-2026-DEMO01')->firstOrFail();

        $this->assertSame(1, $session3->confirmed_seats_count);
        $this->assertSame(BookingStatus::Confirmed, $booking->status);

        app(SessionBookingService::class)->cancelSessionByAdmin(
            $session3,
            $admin,
            'Severe weather warning at Putrajaya field venue.',
        );

        $session3->refresh();
        $booking->refresh();

        $this->assertSame(SessionStatus::Cancelled, $session3->status);
        $this->assertSame(0, $session3->confirmed_seats_count);
        $this->assertSame(0, $session3->reserved_seats_count);
        $this->assertSame(BookingStatus::Cancelled, $booking->status);
    }

    public function test_filament_admin_resources_for_instructors_sessions_and_bookings_are_accessible_to_mfa_admin(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $customer = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();

        $this->actingAs($customer)->get('/admin/instructor-profiles')->assertForbidden();
        $this->actingAs($customer)->get('/admin/training-sessions')->assertForbidden();
        $this->actingAs($customer)->get('/admin/bookings')->assertForbidden();

        $this->actingAs($admin)
            ->withSession(['auth.two_factor_verified' => true])
            ->get('/admin/instructor-profiles')
            ->assertOk()
            ->assertSee('Ir. Siti Norzahra, P.Eng');

        $this->actingAs($admin)
            ->withSession(['auth.two_factor_verified' => true])
            ->get('/admin/training-sessions')
            ->assertOk();

        $this->actingAs($admin)
            ->withSession(['auth.two_factor_verified' => true])
            ->get('/admin/bookings')
            ->assertOk()
            ->assertSee('BKG-2026-DEMO01');
    }

    public function test_mysql_check_constraints_enforce_stage5_session_registration_close_and_capacity_invariants(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $module = Module::query()->where('code', 'EHT-HYD-101')->firstOrFail();

        // Violating chk_sessions_reg_close (registration_closes_at >= starts_at) must fail at MySQL engine level
        $this->expectException(QueryException::class);

        $startsAt = now()->addDays(10);

        DB::table('training_sessions')->insert([
            'module_id' => $module->id,
            'session_code' => 'SES-BAD-001',
            'title' => 'Invalid Registration Close Window Session',
            'slug' => 'invalid-registration-close-window-session',
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHours(8),
            'duration_minutes' => 480,
            'timezone' => 'Asia/Kuala_Lumpur',
            'venue_name' => 'Hall A',
            'venue_address' => 'Address',
            'venue_city' => 'Shah Alam',
            'venue_state' => 'Selangor',
            'capacity' => 20,
            'reserved_seats_count' => 0,
            'confirmed_seats_count' => 0,
            'price' => '500.00',
            'currency' => 'MYR',
            'registration_closes_at' => $startsAt->copy()->addHour(), // Invalid: closes AFTER starts_at
            'recording_access_policy' => 'attendees_only',
            'status' => 'draft',
            'created_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
