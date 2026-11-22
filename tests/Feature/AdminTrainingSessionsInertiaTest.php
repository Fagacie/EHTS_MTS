<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\RoleSlug;
use App\Enums\SessionMaterialStatus;
use App\Jobs\ProcessVideoToHlsJob;
use App\Models\Booking;
use App\Models\Module;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SessionInstructor;
use App\Models\SessionMaterial;
use App\Models\TrainingSession;
use App\Models\User;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SessionAndBookingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
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
        $admin = User::factory()->create();
        $admin->assignRole(RoleSlug::Administrator);

        $this->actingAs($admin)
            ->get(route('admin-vue.sessions.index', ['q' => 'missing-session']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/TrainingSessions/Index')
                ->where('filters.q', 'missing-session')
                ->has('sessions.data')
                ->has('statuses'));
    }

    public function test_administrator_can_create_named_session_without_creating_a_compatibility_module(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleSlug::Administrator);

        $initialModulesCount = Module::withTrashed()->count();

        $this->actingAs($admin)
            ->post(route('admin-vue.sessions.store'), [
                'title' => 'New Hydrology Session',
                'description' => 'A physical training session',
                'starts_at' => now()->addMonth()->format('Y-m-d\TH:i'),
                'ends_at' => now()->addMonth()->addHours(8)->format('Y-m-d\TH:i'),
                'registration_opens_at' => now()->format('Y-m-d\TH:i'),
                'registration_closes_at' => now()->addDays(20)->format('Y-m-d\TH:i'),
                'venue_name' => 'Training Centre',
                'venue_address' => 'Test Address',
                'venue_city' => 'Shah Alam',
                'venue_state' => 'Selangor',
                'capacity' => 25,
                'price' => '500.00',
                'recording_access_policy' => 'attendees_only',
                'status' => 'published',
                'instructors' => [],
            ])
            ->assertRedirect(route('admin-vue.sessions.index'));

        $session = TrainingSession::query()->where('title', 'New Hydrology Session')->firstOrFail();
        $this->assertNull($session->module_id);
        $this->assertSame($initialModulesCount, Module::withTrashed()->count());
    }

    public function test_administrator_can_delete_session_without_historical_records(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleSlug::Administrator);

        $this->actingAs($admin)
            ->post(route('admin-vue.sessions.store'), [
                'title' => 'Deletable Session',
                'description' => 'No bookings or protected records',
                'starts_at' => now()->addMonth()->format('Y-m-d\TH:i'),
                'ends_at' => now()->addMonth()->addHours(8)->format('Y-m-d\TH:i'),
                'registration_opens_at' => now()->format('Y-m-d\TH:i'),
                'registration_closes_at' => now()->addDays(20)->format('Y-m-d\TH:i'),
                'venue_name' => 'Training Centre',
                'venue_address' => 'Test Address',
                'venue_city' => 'Shah Alam',
                'venue_state' => 'Selangor',
                'capacity' => 25,
                'price' => '500.00',
                'recording_access_policy' => 'attendees_only',
                'status' => 'draft',
                'instructors' => [],
            ]);

        $session = TrainingSession::query()->where('title', 'Deletable Session')->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('admin-vue.sessions.destroy', $session))
            ->assertRedirect(route('admin-vue.sessions.index'));

        $this->assertDatabaseMissing('training_sessions', ['id' => $session->id]);
    }

    public function test_assigned_instructor_can_upload_session_material_and_admin_can_review_and_publish_it(): void
    {
        Storage::fake('session-materials');

        $admin = User::factory()->create();
        $admin->assignRole(RoleSlug::Administrator);

        $instructor = User::factory()->create();
        $instructor->assignRole(RoleSlug::Instructor);

        $trainee = User::factory()->create();
        $trainee->assignRole(RoleSlug::Customer);

        $session = TrainingSession::query()->create([
            'module_id' => null,
            'session_code' => 'SES-TEST-001',
            'title' => 'Independent Physical Session',
            'slug' => 'independent-physical-session',
            'description' => 'Test session',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addHours(8),
            'duration_minutes' => 480,
            'timezone' => 'Asia/Kuala_Lumpur',
            'registration_closes_at' => now()->addDays(20),
            'venue_name' => 'Training Centre',
            'capacity' => 25,
            'price' => '500.00',
            'status' => 'published',
            'created_by' => $admin->id,
        ]);

        SessionInstructor::query()->create([
            'training_session_id' => $session->id,
            'instructor_id' => $instructor->id,
            'role_label' => 'Lead Instructor',
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);

        // Assigned instructor uploads teaching material -> pending_review
        $this->actingAs($instructor)
            ->post(route('instructor.sessions.materials.store', $session), [
                'title' => 'Field Calibration Manual',
                'description' => 'Session teaching manual',
                'material_type' => 'pdf',
                'material_file' => UploadedFile::fake()->create('calibration-manual.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('instructor.sessions.show', $session));

        $material = SessionMaterial::query()->where('training_session_id', $session->id)->firstOrFail();
        $this->assertSame(SessionMaterialStatus::PendingReview, $material->status);

        // Admin and assigned instructor can download pending_review file to review it
        $this->actingAs($admin)->get($material->temporaryDownloadUrl(15))->assertOk();
        $this->actingAs($instructor)->get($material->temporaryDownloadUrl(15))->assertOk();

        // Admin approves and publishes the session material
        $this->actingAs($admin)
            ->post(route('admin-vue.sessions.materials.review', ['trainingSession' => $session, 'material' => $material]), [
                'status' => 'published',
                'review_notes' => 'Reviewed and approved for cohort attendees.',
            ])
            ->assertRedirect();

        $this->assertSame(SessionMaterialStatus::Published, $material->fresh()->status);

        // Confirmed attendee can download published session material
        $order = Order::query()->create([
            'order_number' => 'ORD-SES-MAT-01',
            'user_id' => $trainee->id,
            'order_type' => 'session_booking',
            'status' => 'paid',
            'currency' => 'MYR',
            'subtotal_amount' => '500.00',
            'total_amount' => '500.00',
            'customer_name' => $trainee->name,
            'customer_email' => $trainee->email,
            'paid_at' => now(),
        ]);
        $orderItem = OrderItem::query()->create([
            'order_id' => $order->id,
            'order_type' => 'session_booking',
            'item_type' => 'training_session',
            'training_session_id' => $session->id,
            'item_code_snapshot' => $session->session_code,
            'item_title_snapshot' => $session->title,
            'unit_price' => '500.00',
            'quantity' => 1,
            'line_total' => '500.00',
            'currency' => 'MYR',
        ]);
        Booking::query()->create([
            'booking_reference' => 'BKG-SES-MAT-01',
            'user_id' => $trainee->id,
            'training_session_id' => $session->id,
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'status' => BookingStatus::Confirmed,
            'reserved_at' => now(),
            'confirmed_at' => now(),
        ]);

        $this->actingAs($trainee)->get($material->temporaryDownloadUrl(15))->assertOk();
    }

    public function test_administrator_can_upload_additional_document_and_recording_to_session(): void
    {
        Storage::fake('session-materials');
        Storage::fake('recordings');
        Queue::fake([ProcessVideoToHlsJob::class]);

        $admin = User::factory()->create();
        $admin->assignRole(RoleSlug::Administrator);

        $session = TrainingSession::query()->create([
            'module_id' => null,
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
                'file' => UploadedFile::fake()->create('hydrology-worksheet.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('session_materials', [
            'training_session_id' => $session->id,
            'title' => 'Hydrology Worksheet',
            'material_type' => 'worksheet',
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

    public function test_administrator_can_create_standalone_module_without_category_and_upload_module_material(): void
    {
        Storage::fake('materials');

        $admin = User::factory()->create();
        $admin->assignRole(RoleSlug::Administrator);

        $this->actingAs($admin)
            ->post(route('admin-vue.modules.store'), [
                'code' => 'EHT-STANDALONE-01',
                'title' => 'Self-Paced Bathymetric Survey Module',
                'summary' => 'Independent learning module with downloadable manuals.',
                'description' => 'Complete self-paced engineering reference.',
                'learning_objectives' => ['Calibrate single-beam echo sounders'],
                'independent_price' => '350.00',
                'status' => 'published',
            ])
            ->assertRedirect();

        $module = Module::query()->where('code', 'EHT-STANDALONE-01')->firstOrFail();
        $this->assertNull($module->category_id);
        $this->assertTrue($module->is_available_independently);
        $this->assertSame('350.00', (string) $module->independent_price);

        $this->actingAs($admin)
            ->post(route('admin-vue.modules.materials.store', $module), [
                'title' => 'Bathymetric Calculation Workbook',
                'description' => 'Self-paced workbook',
                'material_type' => 'pdf',
                'status' => 'published',
                'is_downloadable' => true,
                'file' => UploadedFile::fake()->create('bathymetric-workbook.pdf', 150, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('module_materials', [
            'module_id' => $module->id,
            'title' => 'Bathymetric Calculation Workbook',
            'status' => 'published',
        ]);
    }

    public function test_confirmed_administrator_can_open_session_workspace(): void
    {
        $this->seed([DemoUserSeeder::class, CatalogueSeeder::class, SessionAndBookingSeeder::class]);

        $admin = User::factory()->create();
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
