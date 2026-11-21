<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Models\TrainingSession;
use App\Models\SessionRecording;
use App\Models\User;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SessionAndBookingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use App\Jobs\ProcessVideoToHlsJob;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminTrainingSessionsInertiaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_confirmed_administrator_can_filter_training_sessions_in_vue(): void
    {
        $admin = User::factory()->create([
        ]);
        $admin->assignRole(RoleSlug::Administrator);

        $this->actingAs($admin)
            ->get(route('admin-vue.sessions.index', ['q' => 'missing-session']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/TrainingSessions/Index')
                ->where('filters.q', 'missing-session')
                ->has('sessions.data')
                ->has('statuses'));
    }

    public function test_administrator_can_create_named_session_without_module_selection(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleSlug::Administrator);

        $this->actingAs($admin)
            ->post(route('admin-vue.sessions.store'), [
                'title' => 'New Hydrology Session',
                'description' => 'A physical training session',
                'starts_at' => now()->addMonth()->format('Y-m-d\\TH:i'),
                'ends_at' => now()->addMonth()->addHours(8)->format('Y-m-d\\TH:i'),
                'registration_opens_at' => now()->format('Y-m-d\\TH:i'),
                'registration_closes_at' => now()->addDays(20)->format('Y-m-d\\TH:i'),
                'venue_name' => 'Training Centre',
                'venue_address' => 'Test Address',
                'venue_city' => 'Shah Alam',
                'venue_state' => 'Selangor',
                'capacity' => 25,
                'price' => '500.00',
                'recording_access_policy' => 'attendees_only',
                'is_available_independently' => false,
                'status' => 'draft',
                'instructors' => [],
            ])
            ->assertRedirect(route('admin-vue.sessions.index'));

        $this->assertDatabaseHas('training_sessions', ['title' => 'New Hydrology Session']);
    }

    public function test_administrator_can_delete_session_without_historical_records(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleSlug::Administrator);

        $this->actingAs($admin)
            ->post(route('admin-vue.sessions.store'), [
                'title' => 'Deletable Session',
                'description' => 'No bookings or protected records',
                'starts_at' => now()->addMonth()->format('Y-m-d\\TH:i'),
                'ends_at' => now()->addMonth()->addHours(8)->format('Y-m-d\\TH:i'),
                'registration_opens_at' => now()->format('Y-m-d\\TH:i'),
                'registration_closes_at' => now()->addDays(20)->format('Y-m-d\\TH:i'),
                'venue_name' => 'Training Centre',
                'venue_address' => 'Test Address',
                'venue_city' => 'Shah Alam',
                'venue_state' => 'Selangor',
                'capacity' => 25,
                'price' => '500.00',
                'recording_access_policy' => 'attendees_only',
                'is_available_independently' => false,
                'status' => 'draft',
                'instructors' => [],
            ]);

        $session = TrainingSession::query()->where('title', 'Deletable Session')->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('admin-vue.sessions.destroy', $session))
            ->assertRedirect(route('admin-vue.sessions.index'));

        $this->assertDatabaseMissing('training_sessions', ['id' => $session->id]);
    }

    public function test_administrator_can_upload_main_learning_document(): void
    {
        Storage::fake('session-materials');

        $admin = User::factory()->create();
        $admin->assignRole(RoleSlug::Administrator);

        $session = TrainingSession::query()->create([
            'module_id' => \App\Models\Module::query()->first()?->id
                ?? \App\Models\Module::query()->create([
                    'category_id' => \App\Models\Category::query()->firstOrCreate(
                        ['slug' => 'training-sessions'],
                        ['name' => 'Training Sessions', 'is_active' => true, 'sort_order' => 0],
                    )->id,
                    'code' => 'TEST-MODULE',
                    'title' => 'Test Module',
                    'slug' => 'test-module',
                    'description' => 'Test module',
                    'is_available_independently' => false,
                    'status' => 'draft',
                    'created_by' => $admin->id,
                ])->id,
            'session_code' => 'SES-TEST-001',
            'title' => 'Learning Material Test',
            'slug' => 'learning-material-test',
            'description' => 'Test session',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addHours(8),
            'duration_minutes' => 480,
            'timezone' => 'Asia/Kuala_Lumpur',
            'registration_closes_at' => now()->addDays(20),
            'venue_name' => 'Training Centre',
            'capacity' => 25,
            'price' => '500.00',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin-vue.sessions.materials.store', $session), [
                'title' => 'Hydrology Manual',
                'description' => 'Main training manual',
                'material_type' => 'pdf',
                'status' => 'published',
                'is_downloadable' => true,
                'is_main_document' => true,
                'is_available_independently' => true,
                'independent_price' => '150.00',
                'file' => UploadedFile::fake()->create('hydrology-manual.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('session_materials', [
            'training_session_id' => $session->id,
            'title' => 'Hydrology Manual',
            'is_main_document' => 1,
        ]);
        $this->assertTrue((bool) $session->fresh()->is_available_independently);
        $this->assertSame('150.00', (string) $session->fresh()->independent_price);
    }

    public function test_administrator_can_upload_additional_document_and_recording_to_session(): void
    {
        Storage::fake('session-materials');
        Storage::fake('recordings');
        Queue::fake([ProcessVideoToHlsJob::class]);

        $admin = User::factory()->create();
        $admin->assignRole(RoleSlug::Administrator);

        $session = TrainingSession::query()->create([
            'module_id' => \App\Models\Module::query()->first()?->id
                ?? \App\Models\Module::query()->create([
                    'category_id' => \App\Models\Category::query()->firstOrCreate(
                        ['slug' => 'training-sessions'],
                        ['name' => 'Training Sessions', 'is_active' => true, 'sort_order' => 0],
                    )->id,
                    'code' => 'TEST-CONTENT-MODULE',
                    'title' => 'Test Content Module',
                    'slug' => 'test-content-module',
                    'description' => 'Test module',
                    'is_available_independently' => false,
                    'status' => 'draft',
                    'created_by' => $admin->id,
                ])->id,
            'session_code' => 'SES-TEST-CONTENT',
            'title' => 'Session Content Test',
            'slug' => 'session-content-test',
            'description' => 'Test session',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addHours(8),
            'duration_minutes' => 480,
            'timezone' => 'Asia/Kuala_Lumpur',
            'registration_closes_at' => now()->addDays(20),
            'venue_name' => 'Training Centre',
            'capacity' => 25,
            'price' => '500.00',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin-vue.sessions.materials.store', $session), [
                'title' => 'Hydrology Worksheet',
                'description' => 'Supporting calculation sheet',
                'material_type' => 'worksheet',
                'status' => 'pending_review',
                'is_downloadable' => true,
                'is_main_document' => false,
                'file' => UploadedFile::fake()->create('hydrology-worksheet.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('session_materials', [
            'training_session_id' => $session->id,
            'title' => 'Hydrology Worksheet',
            'material_type' => 'worksheet',
            'is_main_document' => 0,
        ]);

        $this->actingAs($admin)
            ->post(route('admin-vue.recordings.store'), [
                'training_session_id' => $session->id,
                'title' => 'Hydrology Session Recording',
                'description' => 'Recorded technical session',
                'status' => 'pending_review',
                'file' => UploadedFile::fake()->create('hydrology-session.mp4', 1024, 'video/mp4'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('session_recordings', [
            'training_session_id' => $session->id,
            'title' => 'Hydrology Session Recording',
            'status' => 'pending_review',
        ]);
        Queue::assertPushed(ProcessVideoToHlsJob::class);
    }

    public function test_confirmed_administrator_can_open_session_workspace(): void
    {
        $this->seed([DemoUserSeeder::class, CatalogueSeeder::class, SessionAndBookingSeeder::class]);

        $admin = User::factory()->create([
        ]);
        $admin->assignRole(RoleSlug::Administrator);
        $session = TrainingSession::query()->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin-vue.sessions.show', $session))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/TrainingSessions/Show')
                ->has('session')
                ->has('participants')
                ->has('materials')
                ->has('recordings')
                ->has('attendance')
                ->has('completion'));
    }
}
