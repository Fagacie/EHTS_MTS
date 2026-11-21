<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminFormParityMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function administrator(): User
    {
        $admin = User::factory()->create([
            'password' => Hash::make('Password123!'),
        ]);
        $admin->assignRole(RoleSlug::Administrator);

        return $admin;
    }

    private function authenticatedAdmin(User $admin)
    {
        return $this->actingAs($admin);
    }

    public function test_administrator_logs_in_with_email_and_password(): void
    {
        $admin = $this->administrator();

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'Password123!',
        ])
            ->assertRedirect(route('admin-vue.dashboard'));
    }

    public function test_users_index_supplies_modal_role_and_profile_options(): void
    {
        $admin = $this->administrator();

        $this->authenticatedAdmin($admin)
            ->get(route('admin-vue.users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Index')
                ->has('users.data')
                ->has('roles'));
    }

    public function test_administrator_can_open_vue_form_pages(): void
    {
        $admin = $this->administrator();

        $this->authenticatedAdmin($admin)->get(route('admin-vue.sessions.create'))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('Admin/TrainingSessions/Form')->where('mode', 'create'));

        $this->authenticatedAdmin($admin)->get(route('admin-vue.certificate-settings.edit'))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('Admin/Certificates/Settings')->has('settings'));
    }

    public function test_administrator_can_create_user_with_roles_and_customer_profile(): void
    {
        $admin = $this->administrator();

        $this->authenticatedAdmin($admin)->post(route('admin-vue.users.store'), [
            'name' => 'Test Trainee',
            'email' => 'trainee-form@example.test',
            'password' => 'Password123!',
            'status' => 'active',
            'roles' => ['customer'],
            'profile_customer_type' => 'individual',
            'profile_phone' => '60123456789',
        ])->assertRedirect(route('admin-vue.users.index'));

        $this->assertDatabaseHas('users', ['email' => 'trainee-form@example.test']);
        $this->assertDatabaseHas('customer_profiles', ['phone' => '60123456789']);
    }

    public function test_administrator_can_update_certificate_settings(): void
    {
        $admin = $this->administrator();

        $this->authenticatedAdmin($admin)->post(route('admin-vue.certificate-settings.update'), [
            'issuing_organisation' => 'Ecohydrotech Test',
            'signatory_name' => 'Test Signatory',
            'signatory_title' => 'Managing Director',
            'logo_path' => 'images/ecohydrotech-logo.svg',
        ])->assertRedirect();

        $this->assertDatabaseHas('system_settings', ['setting_key' => 'certificates.signatory_name']);
    }
}
