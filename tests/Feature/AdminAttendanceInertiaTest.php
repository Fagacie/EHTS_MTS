<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminAttendanceInertiaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_confirmed_administrator_can_view_attendance_review_in_vue(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleSlug::Administrator);

        $this->actingAs($admin)
            ->withSession(['auth.two_factor_verified' => true])
            ->get(route('admin-vue.attendance.index', ['review_status' => 'submitted']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Attendance/Index')
                ->where('filters.review_status', 'submitted')
                ->has('attendance.data')
                ->has('reviewStatuses')
                ->has('attendanceStatuses'));
    }
}
