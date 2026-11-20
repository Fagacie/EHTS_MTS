<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\EncodingStatus;
use App\Enums\MaterialStatus;
use App\Enums\ModuleStatus;
use App\Enums\OrderItemType;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\RoleSlug;
use App\Enums\SessionStatus;
use App\Models\Booking;
use App\Models\Category;
use App\Models\InstructorProfile;
use App\Models\Module;
use App\Models\ModuleEntitlement;
use App\Models\ModuleVideo;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TrainingSession;
use App\Models\User;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SessionAndBookingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Stage2PublicPagesInertiaTest extends TestCase
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

    public function test_catalogue_index_supports_inertia_filters_sorting_and_pagination(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $category = Category::query()->where('slug', 'urban-stormwater-msma-design')->firstOrFail();

        for ($i = 1; $i <= 8; $i++) {
            Module::query()->create([
                'category_id' => $category->id,
                'code' => sprintf('EHT-EXT-%03d', $i),
                'title' => sprintf('Hydrology Specialisation Course %02d', $i),
                'slug' => sprintf('hydrology-specialisation-course-%02d', $i),
                'summary' => 'Extended hydrology module for catalogue pagination verification.',
                'description' => 'Detailed technical syllabus content.',
                'learning_objectives' => ['Objective 1'],
                'is_available_independently' => true,
                'independent_price' => (string) (300 + ($i * 25)).'.00',
                'currency' => 'MYR',
                'status' => ModuleStatus::Published->value,
                'published_at' => now()->subDays(20 + $i),
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);
        }

        // Page 1 (9 of 11 published modules)
        $this->get(route('catalogue.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Catalogue/Index')
                ->has('modules', 9)
                ->where('modules.0.code', 'EHT-WQ-105')
                ->where('pagination.currentPage', 1)
                ->where('pagination.lastPage', 2)
                ->where('pagination.total', 11)
                ->where('totalPublishedCount', 11),
            );

        // Page 2 shows remaining 2 modules
        $this->get(route('catalogue.index', ['page' => 2]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Catalogue/Index')
                ->has('modules', 2)
                ->where('pagination.currentPage', 2),
            );

        // Search + availability + sort
        $this->get(route('catalogue.index', ['q' => 'Bio-Retention', 'availability' => 'session_only']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Catalogue/Index')
                ->has('modules', 1)
                ->where('modules.0.code', 'EHT-WQ-105')
                ->where('modules.0.isAvailableIndependently', false),
            );

        $this->get(route('catalogue.index', ['sort' => 'price_asc', 'availability' => 'independent']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Catalogue/Index')
                ->where('modules.0.code', 'EHT-EXT-001')
                ->where('modules.0.independentPriceLabel', 'RM 325.00'),
            );
    }

    public function test_catalogue_show_enforces_entitlement_privacy_and_commercial_separation(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $module = Module::query()->where('code', 'EHT-HYD-101')->firstOrFail();

        $existingVideosCount = ModuleVideo::query()
            ->where('module_id', $module->id)
            ->where('status', MaterialStatus::Published->value)
            ->count();

        $video = ModuleVideo::query()->create([
            'module_id' => $module->id,
            'title' => 'IDF Curve Derivation Walkthrough',
            'description' => 'Step-by-step calculation recording.',
            'provider_video_id' => 'local-hyd-101-99',
            'storage_disk' => 'videos',
            'source_storage_key' => 'videos/private/hyd-101-source.mp4',
            'hls_manifest_key' => 'hls/private/hyd-101/master.m3u8',
            'duration_seconds' => 1845,
            'encoding_status' => EncodingStatus::Ready->value,
            'sort_order' => 99,
            'status' => MaterialStatus::Published->value,
            'published_at' => now(),
            'uploaded_by' => $admin->id,
        ]);

        // Guest sees safe preview without storage keys or signed download/HLS URLs
        $guestResponse = $this->get(route('catalogue.modules.show', $module->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Catalogue/Show')
                ->where('module.code', 'EHT-HYD-101')
                ->where('module.isAvailableIndependently', true)
                ->where('module.independentPriceLabel', 'RM 680.00')
                ->where('module.hasActiveEntitlement', false)
                ->has('materials', 1)
                ->where('materials.0.canDownload', false)
                ->where('materials.0.downloadUrl', null)
                ->missing('materials.0.storage_key')
                ->has('videos', $existingVideosCount + 1)
                ->where("videos.{$existingVideosCount}.title", $video->title)
                ->where("videos.{$existingVideosCount}.durationLabel", '30m 45s')
                ->missing('videos.0.hls_manifest_key')
                ->missing('videos.0.source_storage_key')
                ->missing("videos.{$existingVideosCount}.hls_manifest_key")
                ->missing("videos.{$existingVideosCount}.source_storage_key")
                ->has('upcomingSessions', 1),
            );

        $html = $guestResponse->getContent();
        $this->assertStringNotContainsString('videos/private/hyd-101-source.mp4', $html);
        $this->assertStringNotContainsString('hls/private/hyd-101/master.m3u8', $html);

        // Entitled trainee unlocks material download + active entitlement state
        $order = Order::query()->create([
            'order_number' => 'ORD-ENT-2026-001',
            'user_id' => $trainee->id,
            'order_type' => OrderType::ModulePurchase->value,
            'customer_type' => 'individual',
            'customer_name' => $trainee->name,
            'customer_email' => $trainee->email,
            'customer_phone' => '+6019-245 7710',
            'subtotal_amount' => '680.00',
            'total_amount' => '680.00',
            'currency' => 'MYR',
            'status' => OrderStatus::Paid->value,
            'paid_at' => now(),
        ]);

        $orderItem = OrderItem::query()->create([
            'order_id' => $order->id,
            'order_type' => OrderType::ModulePurchase->value,
            'item_type' => OrderItemType::Module->value,
            'module_id' => $module->id,
            'item_code_snapshot' => $module->code,
            'item_title_snapshot' => $module->title,
            'unit_price' => '680.00',
            'quantity' => 1,
            'line_total' => '680.00',
        ]);

        ModuleEntitlement::query()->create([
            'user_id' => $trainee->id,
            'module_id' => $module->id,
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'grant_source' => 'purchase',
            'status' => 'active',
            'granted_at' => now(),
            'granted_by' => $admin->id,
        ]);

        $this->actingAs($trainee)
            ->get(route('catalogue.modules.show', $module->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Catalogue/Show')
                ->where('module.hasActiveEntitlement', true)
                ->where('materials.0.canDownload', true)
                ->has('materials.0.downloadUrl'),
            );
    }

    public function test_sessions_index_and_show_render_inertia_pages_reconcile_seats_and_guard_404(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();

        $this->get(route('sessions.index', ['availability' => 'open_seats']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Sessions/Index')
                ->has('sessions', 3)
                ->where('pagination.total', 3),
            );

        // Session 3 has an active confirmed booking + 14/15 open seats
        $session3 = TrainingSession::query()->where('session_code', 'SES-2026-003')->firstOrFail();
        Booking::query()
            ->where('training_session_id', $session3->id)
            ->where('status', BookingStatus::Confirmed->value)
            ->firstOrFail();

        // Corrupt counter within chk_sessions_capacity to prove reconcileSessionCounters() repairs it before rendering
        $session3->update(['confirmed_seats_count' => 5]);

        $this->actingAs($trainee)
            ->get(route('sessions.show', $session3))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Sessions/Show')
                ->where('session.sessionCode', 'SES-2026-003')
                ->where('session.remainingSeats', 14)
                ->where('session.seatsOpenLabel', '14 / 15 seats open')
                ->where('canBookAsCustomer', true)
                ->has('existingBooking.bookingReference'),
            );

        // Draft session must return 404
        $draftSession = TrainingSession::query()->create([
            'module_id' => $session3->module_id,
            'session_code' => 'SES-2026-DRAFT',
            'title' => 'Unpublished Hydrology Workshop',
            'slug' => 'unpublished-hydrology-workshop',
            'starts_at' => now()->addDays(20),
            'ends_at' => now()->addDays(20)->addHours(4),
            'registration_closes_at' => now()->addDays(19),
            'duration_minutes' => 240,
            'timezone' => 'Asia/Kuala_Lumpur',
            'venue_name' => 'Private Venue',
            'capacity' => 10,
            'price' => '500.00',
            'currency' => 'MYR',
            'status' => SessionStatus::Draft->value,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->get(route('sessions.show', $draftSession))
            ->assertNotFound();
    }

    public function test_instructors_index_and_show_never_leak_emails_and_enforce_public_visibility(): void
    {
        $publicProfile = InstructorProfile::query()
            ->public()
            ->orderBy('display_name')
            ->firstOrFail();

        $indexResponse = $this->get(route('instructors.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Instructors/Index')
                ->has('instructors', 2)
                ->missing('instructors.0.email')
                ->has('instructors.0.upcomingSessionsCount'),
            );

        $this->assertStringNotContainsString('instructor@ecohydrotech.com.my', $indexResponse->getContent());

        $showResponse = $this->get(route('instructors.show', $publicProfile))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Instructors/Show')
                ->where('instructor.displayName', $publicProfile->resolvedDisplayName())
                ->missing('instructor.email')
                ->has('upcomingSessions'),
            );

        $this->assertStringNotContainsString($publicProfile->user->email, $showResponse->getContent());

        // Deactivating instructor user must hide profile from index and return 404 on show
        $publicProfile->user->forceFill(['status' => 'suspended'])->save();

        $this->get(route('instructors.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('instructors', 1),
            );

        $this->get(route('instructors.show', $publicProfile))
            ->assertNotFound();
    }
}
