<?php

namespace Tests\Feature;

use App\Models\InstructorProfile;
use App\Models\User;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SessionAndBookingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicHomepageInertiaTest extends TestCase
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

    public function test_public_homepage_renders_inertia_page_with_live_seeded_data_and_no_sensitive_leaks(): void
    {
        // Hide one public instructor profile to confirm only active public profiles are exposed
        $amirul = User::query()->where('email', 'staff.trainee@ecohydrotech.com.my')->firstOrFail();
        InstructorProfile::query()
            ->where('user_id', $amirul->id)
            ->update(['is_public' => false]);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Home')
                ->where('app.brandName', 'Ecohydrotech Solution')
                ->where('app.currency', 'MYR')
                ->where('app.displayTimezone', 'Asia/Kuala_Lumpur')
                ->has('app.logoUrl')
                ->where('routes.catalogueIndependent', route('catalogue.index', ['availability' => 'independent']))
                ->where('auth.user', null)
                ->where('currentPath', '/')
                // Active categories only (excludes archived-legacy-programmes)
                ->has('categories', 3)
                ->where('categories.0.slug', 'urban-stormwater-msma-design')
                // Upcoming public sessions (3 seeded active future sessions)
                ->has('upcomingSessions', 3)
                ->where('upcomingSessions.0.sessionCode', 'SES-2026-003')
                ->where('upcomingSessions.0.priceLabel', 'RM 780.00')
                ->where('upcomingSessions.0.remainingSeats', 14)
                ->where('upcomingSessions.0.isOpenForBooking', true)
                ->has('upcomingSessions.0.dateRangeLabel')
                ->has('upcomingSessions.0.timeLabel')
                ->has('upcomingSessions.0.isThisMonth')
                // Published modules only (excludes draft EHT-HYD-399)
                ->has('featuredModules', 3)
                ->where('featuredModules.0.code', 'EHT-WQ-105')
                ->has('featuredModules.0.categorySlug')
                ->missing('featuredModules.0.storage_key')
                ->missing('featuredModules.0.hls_manifest_key')
                // Only public instructor profile is shown; email is never leaked
                ->has('instructors', 1)
                ->where('instructors.0.displayName', 'Ir. Siti Norzahra, P.Eng')
                ->missing('instructors.0.email')
                // Live database counters & SystemSetting reservation hold
                ->where('stats.publishedModulesCount', 3)
                ->where('stats.independentModulesCount', 2)
                ->where('stats.upcomingSessionsCount', 3)
                ->where('stats.activeCategoriesCount', 3)
                ->where('stats.publicInstructorsCount', 1)
                ->where('stats.reservationHoldMinutes', 15)
                // Static & WebP public images exist
                ->has('assets.heroPhotoUrl')
                ->has('assets.heroPhotoWebpUrl')
                ->has('assets.classroomPhotoUrl')
                ->has('assets.classroomPhotoWebpUrl'),
            );

        $html = $response->getContent();
        $this->assertStringNotContainsString('EHT-HYD-399', $html);
        $this->assertStringNotContainsString('archived-legacy-programmes', $html);
        $this->assertStringNotContainsString('instructor@ecohydrotech.com.my', $html);
    }

    public function test_public_homepage_shares_minimal_authenticated_user_and_multi_role_portals_without_secrets(): void
    {
        $dualStaff = User::query()->where('email', 'staff.trainee@ecohydrotech.com.my')->firstOrFail();

        $this->actingAs($dualStaff)
            ->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Home')
                ->where('auth.user.id', $dualStaff->id)
                ->where('auth.user.name', $dualStaff->name)
                ->where('auth.user.email', $dualStaff->email)
                ->where('auth.user.isCustomer', true)
                ->where('auth.user.isInstructor', true)
                ->where('auth.user.isAdministrator', false)
                ->where('auth.user.defaultDashboardUrl', route('instructor.dashboard'))
                ->has('auth.user.availablePortals', 2)
                ->where('auth.user.availablePortals.0.slug', 'customer')
                ->where('auth.user.availablePortals.1.slug', 'instructor')
                ->missing('auth.user.password')
                ->missing('auth.user.two_factor_secret')
                ->missing('auth.user.two_factor_recovery_codes')
                ->missing('auth.user.remember_token'),
            );
    }

    public function test_legacy_blade_livewire_and_filament_routes_continue_coexisting_alongside_inertia_homepage(): void
    {
        $this->get(route('catalogue.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Catalogue/Index'),
            );

        $this->get(route('sessions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Sessions/Index'),
            )
            ->assertSee('SES-2026-001');

        $this->get(route('instructors.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Instructors/Index'),
            )
            ->assertSee('Ir. Siti Norzahra, P.Eng');

        $this->get(route('login'))
            ->assertOk();

        $this->get(route('register'))
            ->assertOk();

        $this->get('/admin')
            ->assertNotFound();

        $this->get(route('admin-vue.dashboard'))
            ->assertRedirect(route('login'));
    }
}
