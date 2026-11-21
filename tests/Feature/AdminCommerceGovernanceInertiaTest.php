<?php

namespace Tests\Feature;

use App\Enums\EntitlementStatus;
use App\Enums\RoleSlug;
use App\Models\Category;
use App\Models\Module;
use App\Models\ModuleEntitlement;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminCommerceGovernanceInertiaTest extends TestCase
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

    public function test_administrator_can_open_governance_pages(): void
    {
        $admin = $this->administrator();

        foreach ([
            'admin-vue.orders.index' => 'Admin/Orders/Index',
            'admin-vue.module-entitlements.index' => 'Admin/ModuleEntitlements/Index',
            'admin-vue.audit-logs.index' => 'Admin/AuditLogs/Index',
            'admin-vue.notification-deliveries.index' => 'Admin/NotificationDeliveries/Index',
        ] as $route => $component) {
            $this->actingAs($admin)
                ->withSession(['auth.two_factor_verified' => true])
                ->get(route($route))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component($component));
        }
    }

    public function test_manual_grant_and_revoke_preserve_audit_and_entitlement_state(): void
    {
        $admin = $this->administrator();
        $customer = User::factory()->create();
        $customer->assignRole(RoleSlug::Customer);

        $category = Category::query()->create([
            'name' => 'Safety',
            'slug' => 'safety',
            'description' => null,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $module = Module::query()->create([
            'category_id' => $category->id,
            'code' => 'SAFE-001',
            'title' => 'Safety Essentials',
            'slug' => 'safety-essentials',
            'description' => 'Test module',
            'is_available_independently' => true,
            'independent_price' => 100,
            'currency' => 'MYR',
            'status' => 'published',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->withSession(['auth.two_factor_verified' => true])
            ->post(route('admin-vue.module-entitlements.grant'), [
                'user_id' => $customer->id,
                'module_id' => $module->id,
                'grant_reason' => 'Approved training sponsorship.',
            ])
            ->assertSessionHas('success');

        $entitlement = ModuleEntitlement::query()->where('user_id', $customer->id)->where('module_id', $module->id)->firstOrFail();
        $this->assertSame(EntitlementStatus::Active, $entitlement->status);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'module_entitlement.admin_granted',
            'auditable_type' => ModuleEntitlement::class,
            'auditable_id' => $entitlement->id,
        ]);

        $this->actingAs($admin)
            ->withSession(['auth.two_factor_verified' => true])
            ->post(route('admin-vue.module-entitlements.revoke', $entitlement), [
                'revocation_reason' => 'Training sponsorship ended.',
            ])
            ->assertSessionHas('success');

        $this->assertSame(EntitlementStatus::Revoked, $entitlement->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'module_entitlement.revoked',
            'auditable_type' => ModuleEntitlement::class,
            'auditable_id' => $entitlement->id,
        ]);
    }

    public function test_non_administrator_cannot_open_governance_pages(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(RoleSlug::Customer);

        $this->actingAs($customer)
            ->get(route('admin-vue.orders.index'))
            ->assertForbidden();
    }
}
