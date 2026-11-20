<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Certificate;
use App\Models\Order;
use App\Models\User;
use App\Services\Notification\EtmsNotificationService;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\CertificateSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SessionAndBookingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PortalLayoutFoundationTest extends TestCase
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

    public function test_trainee_portal_navigation_uses_seven_sidebar_items_header_account_menu_and_role_switching(): void
    {
        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $dualStaff = User::query()->where('email', 'staff.trainee@ecohydrotech.com.my')->firstOrFail();

        $beforeUnreadCount = $trainee->unreadNotifications()->count();

        app(EtmsNotificationService::class)->notifyUser(
            user: $trainee,
            eventType: 'booking.confirmed',
            title: 'Foundation Navigation Badge Test',
            message: 'Ensures unread counter renders cleanly across sidebar and header notifications.',
        );

        // Migrated Inertia /portal dashboard exposes unread notification count, routes & multi-role switcher
        $this->actingAs($trainee)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Dashboard')
                ->where('portalMeta.unreadNotificationsCount', $beforeUnreadCount + 1)
                ->where('routes.portalModules', route('portal.modules.index'))
                ->where('routes.portalBookings', route('portal.bookings.index'))
                ->where('routes.portalCertificates', route('portal.certificates.index'))
                ->where('routes.portalOrders', route('portal.orders.index'))
                ->where('routes.portalNotifications', route('portal.notifications.index'))
                ->where('routes.portalProfile', route('portal.profile.edit'))
                ->where('routes.portalSecurity', route('portal.security.edit')),
            );

        // Migrated /portal/bookings renders Inertia Portal/Bookings/Index while unmigrated Blade /checkout/return retains portal.blade.php
        $this->actingAs($trainee)
            ->get(route('portal.bookings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Bookings/Index')
                ->has('bookings', 1),
            );

        $order = Order::query()->where('user_id', $trainee->id)->firstOrFail();

        // /checkout/return renders Inertia Checkout/Return while legacy portal.blade.php remains intact for coexistence
        $this->actingAs($trainee)
            ->get(route('checkout.return', ['order' => $order->order_number]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Checkout/Return')
                ->where('order.orderNumber', $order->order_number),
            );

        $this->actingAs($trainee);
        $html = view('components.layouts.portal', [
            'title' => 'Shell Verification',
            'slot' => new \Illuminate\Support\HtmlString(''),
        ])->render();

        preg_match('/<aside[^>]*id="portal-sidebar"[^>]*>(.*?)<\/aside>/s', $html, $asideMatch);
        $this->assertNotEmpty($asideMatch[1] ?? '');
        $asideHtml = $asideMatch[1];

        $expectedOrder = [
            'Dashboard',
            'Browse Training',
            'My Learning',
            'My Bookings',
            'Certificates',
            'Orders &amp; Payments',
            'Notifications',
        ];

        $lastPos = -1;
        foreach ($expectedOrder as $label) {
            $pos = strpos($asideHtml, $label);
            $this->assertNotFalse($pos, "Sidebar missing navigation item: {$label}");
            $this->assertGreaterThan($lastPos, $pos, "Sidebar order mismatch at: {$label}");
            $lastPos = $pos;
        }

        // Duplicate account destinations and old labels must NOT appear in sidebar
        $this->assertStringNotContainsString('Workspace', $asideHtml);
        $this->assertStringNotContainsString('Session Recordings', $asideHtml);
        $this->assertStringNotContainsString('Profile &amp; Billing', $asideHtml);
        $this->assertStringNotContainsString('Password &amp; Security', $asideHtml);
        $this->assertStringNotContainsString('w-0.5 rounded-r', $asideHtml);

        // Account menu owns Profile & Billing, Password & Security, and Sign Out
        preg_match('/id="portal-profile-menu"[^>]*>(.*?)<\/header>/s', $html, $menuMatch);
        $this->assertNotEmpty($menuMatch[1] ?? '');
        $menuHtml = $menuMatch[1];

        $this->assertStringContainsString('Profile &amp; Billing', $menuHtml);
        $this->assertStringContainsString('Password &amp; Security', $menuHtml);
        $this->assertStringContainsString('Sign Out', $menuHtml);

        // Desktop sidebar collapse and mobile drawer controls exist
        $this->assertStringContainsString('id="portal-sidebar-collapse"', $html);
        $this->assertStringContainsString('id="portal-drawer-open"', $html);
        $this->assertStringContainsString('id="portal-drawer-close"', $html);

        // Multi-role portal switcher appears in Inertia portalMeta for dual-role users
        $this->actingAs($dualStaff)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Dashboard')
                ->where('auth.user.isInstructor', true)
                ->has('portalMeta.availablePortals', 2)
                ->where('portalMeta.availablePortals.1.label', 'Instructor Portal')
                ->where('portalMeta.availablePortals.1.url', route('instructor.dashboard')),
            );
    }

    public function test_all_redesigned_trainee_portal_pages_render_and_pass_ui_hygiene_checks(): void
    {
        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $booking = Booking::query()->where('user_id', $trainee->id)->firstOrFail();
        $order = Order::query()->where('user_id', $trainee->id)->firstOrFail();
        $certificate = Certificate::query()->where('user_id', $trainee->id)->firstOrFail();

        $routes = [
            route('portal.dashboard'),
            route('portal.modules.index'),
            route('portal.recordings.index'),
            route('portal.bookings.index'),
            route('portal.bookings.show', $booking),
            route('portal.certificates.index'),
            route('portal.certificates.show', $certificate),
            route('portal.orders.index'),
            route('portal.orders.show', $order),
            route('portal.notifications.index'),
            route('portal.profile.edit'),
            route('portal.security.edit'),
        ];

        foreach ($routes as $url) {
            $this->actingAs($trainee)
                ->get($url)
                ->assertOk()
                ->assertDontSee('D-001')
                ->assertDontSee('D-017')
                ->assertDontSee('TRN-');
        }

        $bladeFiles = array_merge(
            File::allFiles(resource_path('views/portal')),
            File::allFiles(resource_path('views/components/portal')),
            [new \SplFileInfo(resource_path('views/components/layouts/portal.blade.php'))]
        );

        foreach ($bladeFiles as $file) {
            $contents = File::get($file->getPathname());

            $this->assertDoesNotMatchRegularExpression(
                '/(<x-eht\.|text-\[10px\]|text-\[11px\]|TRN-|FR-[0-9]|D-[0-9]{3}|Cloudflare|Bunny|portal-navy|portal-indigo|portal-coral)/',
                $contents,
                "Portal view failed redesign hygiene check: {$file->getPathname()}"
            );
        }
    }

    public function test_profile_and_security_forms_expose_accessible_controls_and_inline_validation_feedback(): void
    {
        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();

        $this->actingAs($trainee)
            ->get(route('portal.profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Profile')
                ->where('user.email', $trainee->email)
                ->where('profile.billingCountry', 'MY')
                ->where('updateUrl', route('portal.profile.update')),
            );

        // Company selection without company name returns inline validation feedback while keeping company value active
        $this->actingAs($trainee)
            ->from(route('portal.profile.edit'))
            ->put(route('portal.profile.update'), [
                'name' => 'Preserved Name Value',
                'email' => $trainee->email,
                'customer_type' => 'company',
                'phone' => '+60 12-999 0000',
                'company_name' => '',
                'billing_country' => 'MY',
            ])
            ->assertRedirect(route('portal.profile.edit'))
            ->assertSessionHasErrors('company_name');

        $this->actingAs($trainee)
            ->get(route('portal.security.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Security')
                ->where('user.email', $trainee->email)
                ->where('user.isActive', true)
                ->where('updatePasswordUrl', route('portal.security.password.update')),
            );
    }
}
