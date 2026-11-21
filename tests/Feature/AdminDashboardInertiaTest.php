<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminDashboardInertiaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_confirmed_administrator_can_open_vue_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleSlug::Administrator);

        $this->actingAs($admin)
            ->get(route('admin-vue.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->has('report.customers')
                ->has('report.revenue')
                ->has('upcomingSessions')
                ->has('pendingActions')
                ->has('revenueTrend')
                ->has('sessionPerformance')
                ->has('bookingDistribution')
                ->has('capacitySessions')
                ->has('recentActivity'));
    }

    public function test_non_administrator_cannot_open_vue_dashboard(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(RoleSlug::Customer);

        $this->actingAs($customer)
            ->get(route('admin-vue.dashboard'))
            ->assertForbidden();
    }
}
