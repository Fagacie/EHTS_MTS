<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class Stage3FoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_public_homepage_loads_with_ecohydrotech_design_system(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Ecohydrotech');
        $response->assertSee('Physical Training Sessions');
        $response->assertSee('Independent Module Purchases');
    }

    public function test_customer_registration_assigns_customer_role_writes_audit_log_and_requires_verification(): void
    {
        Notification::fake();

        $response = $this->post(route('register.store'), [
            'name' => 'Nurul Aina',
            'email' => 'aina@ecohydrotech.test',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'aina@ecohydrotech.test')->firstOrFail();
        $this->assertTrue($user->isCustomer());
        $this->assertFalse($user->isInstructor());
        $this->assertFalse($user->isAdministrator());
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertNull($user->email_verified_at);

        Notification::assertSentTo($user, VerifyEmail::class);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'user.registered',
            'auditable_type' => 'users',
            'auditable_id' => $user->id,
        ]);

        // Unverified customer cannot access /portal directly
        $this->get(route('portal.dashboard'))
            ->assertRedirect(route('verification.notice'));

        // Signed verification link verifies email and unlocks /portal
        $verifyUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->get($verifyUrl)->assertRedirect(route('portal.dashboard'));
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->get(route('portal.dashboard'))->assertOk();
    }

    public function test_password_reset_flow_updates_password_and_allows_login(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'reset@ecohydrotech.test']);
        $user->assignRole(RoleSlug::Customer);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        $this->assertNotNull($token);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword456!',
            'password_confirmation' => 'NewPassword456!',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NewPassword456!', $user->fresh()->password));
    }

    public function test_suspended_and_deactivated_users_cannot_login_or_stay_authenticated(): void
    {
        $suspended = User::factory()->suspended()->create([
            'email' => 'suspended@ecohydrotech.test',
            'password' => Hash::make('Password123!'),
        ]);
        $suspended->assignRole(RoleSlug::Customer);

        $this->post(route('login.store'), [
            'email' => $suspended->email,
            'password' => 'Password123!',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $deactivated = User::factory()->deactivated()->create([
            'email' => 'deactivated@ecohydrotech.test',
        ]);
        $deactivated->assignRole(RoleSlug::Customer);

        $this->actingAs($deactivated)
            ->get(route('portal.dashboard'))
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_strict_three_role_boundaries_across_portals(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(RoleSlug::Customer);

        $instructor = User::factory()->create();
        $instructor->assignRole(RoleSlug::Instructor);

        $admin = User::factory()->create([
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
            'two_factor_confirmed_at' => now(),
        ]);
        $admin->assignRole(RoleSlug::Administrator);

        // Customer boundaries
        $this->actingAs($customer)->get(route('portal.dashboard'))->assertOk();
        $this->actingAs($customer)->get(route('portal.security.edit'))->assertOk();
        $this->actingAs($customer)->get(route('instructor.dashboard'))->assertForbidden();
        $this->actingAs($customer)->get('/admin')->assertForbidden();

        // Instructor boundaries
        $this->actingAs($instructor)->get(route('instructor.dashboard'))->assertOk();
        $this->actingAs($instructor)->get(route('portal.dashboard'))->assertForbidden();
        $this->actingAs($instructor)->get('/admin')->assertForbidden();

        // Single-role Administrator cannot enter Trainee or Instructor portals unless assigned those roles (D-026)
        $this->actingAs($admin)
            ->withSession(['auth.two_factor_verified' => true])
            ->get(route('portal.dashboard'))
            ->assertForbidden();
        $this->actingAs($admin)
            ->withSession(['auth.two_factor_verified' => true])
            ->get(route('instructor.dashboard'))
            ->assertForbidden();
    }

    public function test_multi_role_staff_can_switch_between_instructor_and_trainee_portals(): void
    {
        $staffTrainee = User::factory()->create();
        $staffTrainee->syncRolesBySlugs([RoleSlug::Instructor, RoleSlug::Customer]);

        $this->actingAs($staffTrainee)
            ->get(route('instructor.dashboard'))
            ->assertOk()
            ->assertSee('Trainee Portal')
            ->assertSee('Instructor Portal');

        $this->actingAs($staffTrainee)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee('Trainee Portal')
            ->assertSee('Instructor Portal');
    }

    public function test_administrator_mfa_enforcement_and_filament_panel_access(): void
    {
        $adminWithoutMfa = User::factory()->create();
        $adminWithoutMfa->assignRole(RoleSlug::Administrator);

        // Admin without configured TOTP is routed to setup
        $this->actingAs($adminWithoutMfa)
            ->get('/admin')
            ->assertRedirect(route('two-factor.setup'));

        $secret = 'JBSWY3DPEHPK3PXP';
        $adminWithMfa = User::factory()->create([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => ['ABCD-1234', 'EFGH-5678'],
            'two_factor_confirmed_at' => now(),
        ]);
        $adminWithMfa->assignRole(RoleSlug::Administrator);

        // Admin with TOTP configured must complete challenge before accessing Filament
        $this->actingAs($adminWithMfa)
            ->get('/admin')
            ->assertRedirect(route('two-factor.challenge'));

        $otp = (new Google2FA)->getCurrentOtp($secret);

        $this->actingAs($adminWithMfa)
            ->post(route('two-factor.challenge.verify'), ['code' => $otp])
            ->assertRedirect(route('filament.admin.pages.dashboard'));

        $this->actingAs($adminWithMfa)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Ecohydrotech ETMS Admin');

        $this->actingAs($adminWithMfa)
            ->get('/admin/users')
            ->assertOk();

        $this->actingAs($adminWithMfa)
            ->get('/admin/audit-logs')
            ->assertOk();
    }

    public function test_mysql_check_constraints_reject_invalid_status_and_role_slug(): void
    {
        $this->expectException(QueryException::class);
        DB::table('users')->insert([
            'name' => 'Invalid Status User',
            'email' => 'badstatus@ecohydrotech.test',
            'password' => Hash::make('Password123!'),
            'status' => 'banned_unknown',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_mysql_check_constraint_rejects_non_approved_role_slug(): void
    {
        $this->expectException(QueryException::class);
        DB::table('roles')->insert([
            'slug' => 'corporate_coordinator',
            'name' => 'Corporate Coordinator',
            'is_system' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_demo_seeder_provisions_expected_accounts_and_roles(): void
    {
        $this->seed(DemoUserSeeder::class);

        $this->assertDatabaseCount('roles', 3);
        $this->assertDatabaseCount('permissions', 20);

        $dual = User::query()->where('email', 'staff.trainee@ecohydrotech.com.my')->firstOrFail();
        $this->assertTrue($dual->isInstructor());
        $this->assertTrue($dual->isCustomer());
        $this->assertFalse($dual->isAdministrator());
    }
}
