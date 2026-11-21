<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminContentInertiaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function administrator(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleSlug::Administrator);

        return $admin;
    }

    public function test_confirmed_administrator_can_view_content_admin_surfaces(): void
    {
        $admin = $this->administrator();
        $session = $this->actingAs($admin);

        $session->get(route('admin-vue.announcements.index'))
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Content/Announcements/Index')->has('announcements'));
        $session->get(route('admin-vue.instructor-profiles.index'))
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Content/InstructorProfiles/Index')->has('profiles'));
    }

    public function test_administrator_can_create_instructor_account_without_selecting_an_account(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)
            ->post(route('admin-vue.instructor-profiles.store'), [
                'name' => 'New Instructor',
                'email' => 'new.instructor@example.test',
                'password' => 'Password123!',
                'professional_title' => 'Hydrology Instructor',
                'qualifications' => 'B.Eng',
                'specialisations' => ['Hydrology'],
                'is_public' => true,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['email' => 'new.instructor@example.test']);
        $this->assertDatabaseHas('instructor_profiles', ['display_name' => 'New Instructor']);
    }

}
