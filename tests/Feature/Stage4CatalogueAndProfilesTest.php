<?php

namespace Tests\Feature;

use App\Enums\CustomerType;
use App\Enums\MaterialStatus;
use App\Enums\MaterialType;
use App\Enums\ModuleStatus;
use App\Enums\RoleSlug;
use App\Models\Category;
use App\Models\Module;
use App\Models\ModuleMaterial;
use App\Models\User;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Stage4CatalogueAndProfilesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_company_registration_creates_customer_profile_and_portal_profile_can_be_updated(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Ir. Hafizuddin Razak',
            'email' => 'hafiz@hidroconsult.my',
            'phone' => '+6012-900 1122',
            'customer_type' => 'company',
            'company_name' => 'HidroConsult Engineering Sdn. Bhd.',
            'company_registration_no' => '202101009988',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertRedirect(route('verification.notice'));

        $user = User::query()->where('email', 'hafiz@hidroconsult.my')->firstOrFail();
        $this->assertNotNull($user->customerProfile);
        $this->assertSame(CustomerType::Company, $user->customerProfile->customer_type);
        $this->assertSame('HidroConsult Engineering Sdn. Bhd.', $user->customerProfile->company_name);

        // Verify email and update full billing profile in Trainee Portal
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($user)
            ->get(route('portal.profile.edit'))
            ->assertOk()
            ->assertSee('HidroConsult Engineering Sdn. Bhd.');

        $this->actingAs($user)
            ->put(route('portal.profile.update'), [
                'name' => 'Ir. Hafizuddin Razak',
                'email' => 'hafiz@hidroconsult.my',
                'customer_type' => 'company',
                'phone' => '+6012-900 1122',
                'job_title' => 'Technical Director',
                'company_name' => 'HidroConsult Engineering Sdn. Bhd.',
                'company_registration_no' => '202101009988',
                'billing_contact_name' => 'Accounts Payable',
                'billing_email' => 'ap@hidroconsult.my',
                'billing_phone' => '+603-7788 9900',
                'billing_address_line_1' => 'Level 12, Menara Ecohydro',
                'billing_city' => 'Petaling Jaya',
                'billing_state' => 'Selangor',
                'billing_postcode' => '46200',
                'billing_country' => 'MY',
            ])
            ->assertRedirect(route('portal.profile.edit'));

        $profile = $user->fresh()->customerProfile;
        $this->assertTrue($profile->isComplete());
        $this->assertSame('Petaling Jaya', $profile->billing_city);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'customer_profile.updated',
            'auditable_type' => 'customer_profiles',
            'auditable_id' => $profile->id,
        ]);
    }

    public function test_public_catalogue_filters_by_category_search_and_visibility_rules(): void
    {
        $this->seed([
            DemoUserSeeder::class,
            CatalogueSeeder::class,
        ]);

        // Public catalogue shows published modules in active categories and hides drafts
        $this->get(route('catalogue.index'))
            ->assertOk()
            ->assertSee('EHT-HYD-101')
            ->assertSee('Applied Urban Stormwater Management')
            ->assertSee('EHT-FLD-201')
            ->assertSee('EHT-WQ-105')
            ->assertDontSee('EHT-HYD-399');

        // Filter by category slug
        $this->get(route('catalogue.index', ['category' => 'river-basin-flood-modelling']))
            ->assertOk()
            ->assertSee('EHT-FLD-201')
            ->assertDontSee('EHT-HYD-101');

        // Filter by search keyword
        $this->get(route('catalogue.index', ['q' => 'Bio-Retention']))
            ->assertOk()
            ->assertSee('EHT-WQ-105')
            ->assertDontSee('EHT-FLD-201');

        // Filter by independent self-paced availability
        $this->get(route('catalogue.index', ['availability' => 'independent']))
            ->assertOk()
            ->assertSee('EHT-HYD-101')
            ->assertDontSee('EHT-WQ-105');
    }

    public function test_module_detail_page_renders_published_module_and_returns_404_for_draft_or_inactive_category(): void
    {
        $this->seed([
            DemoUserSeeder::class,
            CatalogueSeeder::class,
        ]);

        $published = Module::query()->where('code', 'EHT-HYD-101')->firstOrFail();
        $draft = Module::query()->where('code', 'EHT-HYD-399')->firstOrFail();

        $this->get(route('catalogue.modules.show', $published->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Catalogue/Show')
                ->where('module.code', 'EHT-HYD-101')
                ->where('module.independentPriceLabel', 'RM 680.00')
                ->where('materials.0.title', 'MSMA 2nd Edition Design Calculation Workbook')
                ->where('materials.0.canDownload', false)
                ->where('materials.0.downloadUrl', null),
            )
            ->assertDontSee('modules/materials/eht-hyd-101-msma-workbook.pdf');

        // Draft module must return 404
        $this->get(route('catalogue.modules.show', $draft->slug))
            ->assertNotFound();

        // Module under an archived/inactive category must return 404
        $published->category->update(['is_active' => false]);
        $this->get(route('catalogue.modules.show', $published->slug))
            ->assertNotFound();
    }

    public function test_learning_material_download_enforces_entitlement_and_admin_access_policy(): void
    {
        Storage::fake('materials');

        $admin = User::factory()->create([
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
            'two_factor_confirmed_at' => now(),
        ]);
        $admin->assignRole(RoleSlug::Administrator);

        $customer = User::factory()->create();
        $customer->assignRole(RoleSlug::Customer);

        $category = Category::query()->create([
            'name' => 'Hydrology',
            'slug' => 'hydrology',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $module = Module::query()->create([
            'category_id' => $category->id,
            'code' => 'EHT-TEST-100',
            'title' => 'Hydrological Modelling Fundamentals',
            'slug' => 'hydrological-modelling-fundamentals',
            'description' => 'Full technical syllabus.',
            'is_available_independently' => true,
            'independent_price' => '550.00',
            'currency' => 'MYR',
            'status' => ModuleStatus::Published->value,
            'published_at' => now(),
            'created_by' => $admin->id,
        ]);

        $storageKey = 'modules/test-handbook.pdf';
        $fileContent = '%PDF-1.4 Private Server Storage Handbook Content';
        Storage::disk('materials')->put($storageKey, $fileContent);

        $material = ModuleMaterial::query()->create([
            'module_id' => $module->id,
            'title' => 'Hydrological Modelling Handbook',
            'material_type' => MaterialType::Pdf->value,
            'storage_disk' => 'materials',
            'storage_bucket' => 'etms-server-storage',
            'storage_key' => $storageKey,
            'original_filename' => 'Hydrological-Handbook.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => strlen($fileContent),
            'checksum_sha256' => hash('sha256', $fileContent),
            'is_downloadable' => true,
            'sort_order' => 1,
            'status' => MaterialStatus::Published->value,
            'published_at' => now(),
            'uploaded_by' => $admin->id,
        ]);

        // Guest is redirected to login
        $this->get(route('materials.download', $material))
            ->assertRedirect(route('login'));

        // Unentitled customer receives 403 Forbidden (D-018, FR-07)
        $this->actingAs($customer)
            ->get(route('materials.download', $material))
            ->assertForbidden();

        // Administrator with modules.manage permission can download and audit log is recorded
        $this->actingAs($admin)
            ->get(route('materials.download', $material))
            ->assertOk()
            ->assertDownload('Hydrological-Handbook.pdf');

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'module_material.downloaded',
            'auditable_type' => 'module_materials',
            'auditable_id' => $material->id,
            'actor_id' => $admin->id,
        ]);
    }

    public function test_filament_admin_resources_for_categories_modules_and_materials_are_accessible_to_mfa_admin(): void
    {
        $this->seed([
            DemoUserSeeder::class,
            CatalogueSeeder::class,
        ]);

        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $customer = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();

        $this->actingAs($customer)->get('/admin/categories')->assertForbidden();
        $this->actingAs($customer)->get('/admin/modules')->assertForbidden();
        $this->actingAs($customer)->get('/admin/module-materials')->assertForbidden();

        $this->actingAs($admin)
            ->withSession(['auth.two_factor_verified' => true])
            ->get('/admin/categories')
            ->assertOk()
            ->assertSee('Urban Stormwater');

        $this->actingAs($admin)
            ->withSession(['auth.two_factor_verified' => true])
            ->get('/admin/modules')
            ->assertOk()
            ->assertSee('EHT-HYD-101');

        $this->actingAs($admin)
            ->withSession(['auth.two_factor_verified' => true])
            ->get('/admin/module-materials')
            ->assertOk()
            ->assertSee('MSMA 2nd Edition Design Calculation Workbook');
    }

    public function test_mysql_check_constraints_enforce_stage4_domain_invariants(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleSlug::Administrator);

        $category = Category::query()->create([
            'name' => 'General Hydrology',
            'slug' => 'general-hydrology',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // Independent module with NULL independent_price must violate chk_modules_independent_price
        $this->expectException(QueryException::class);

        DB::table('modules')->insert([
            'category_id' => $category->id,
            'code' => 'EHT-BAD-001',
            'title' => 'Invalid Independent Module Without Price',
            'slug' => 'invalid-independent-module-without-price',
            'description' => 'Should be rejected by chk_modules_independent_price.',
            'is_available_independently' => true,
            'independent_price' => null,
            'currency' => 'MYR',
            'status' => 'draft',
            'created_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
