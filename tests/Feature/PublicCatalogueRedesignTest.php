<?php

namespace Tests\Feature;

use App\Enums\ModuleStatus;
use App\Enums\RoleSlug;
use App\Livewire\Public\TrainingCatalogue;
use App\Models\Category;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class PublicCatalogueRedesignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleAndPermissionSeeder::class,
            DemoUserSeeder::class,
            CatalogueSeeder::class,
        ]);
    }

    public function test_catalogue_page_renders_editorial_header_cards_navigation_and_auth_states(): void
    {
        // Attach a cover image to EHT-HYD-101 to verify both cover-image and neutral-fallback cards render cleanly
        $hydModule = Module::query()->where('code', 'EHT-HYD-101')->firstOrFail();
        $hydModule->update([
            'cover_image_disk' => 'public',
            'cover_image_path' => 'modules/covers/msma-cover.jpg',
        ]);

        // Guest state & public navigation
        $guestResponse = $this->get(route('catalogue.index'))
            ->assertOk()
            ->assertSee('Explore Our Training Programmes')
            ->assertSee('Training Catalogue')
            ->assertSee('Physical Sessions')
            ->assertSee('Instructors')
            ->assertSee('Verify Certificate')
            ->assertSee('Log In')
            ->assertSee('Create Account')
            ->assertSee('Available programmes')
            ->assertSee('EHT-HYD-101')
            ->assertSee('modules/covers/msma-cover.jpg')
            ->assertSee('Self-paced module')
            ->assertSee('Lifetime access')
            ->assertSee('RM 680.00')
            ->assertSee('Physical session only')
            ->assertSee('View Programme')
            ->assertDontSee('D-005')
            ->assertDontSee('D-018')
            ->assertDontSee('D-024');

        $html = $guestResponse->getContent();
        $this->assertMatchesRegularExpression('/Available programmes/i', $html);

        // Authenticated state preserves My Portal and multi-role switcher
        $dualStaff = User::query()->where('email', 'staff.trainee@ecohydrotech.com.my')->firstOrFail();
        $this->actingAs($dualStaff)
            ->get(route('catalogue.index'))
            ->assertOk()
            ->assertSee('My Portal')
            ->assertSee('Trainee Portal')
            ->assertSee('Instructor Portal')
            ->assertSee('Sign Out');

        // Unauthenticated customer role check remains intact
        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $this->assertTrue($trainee->hasRole(RoleSlug::Customer));
    }

    public function test_livewire_catalogue_filtering_sorting_clear_action_and_pagination(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $category = Category::query()->where('slug', 'urban-stormwater-msma-design')->firstOrFail();

        // Seed 8 additional published modules (3 seeded + 8 added = 11 total -> spans 2 pages at 9 per page)
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

        Livewire::test(TrainingCatalogue::class)
            ->assertSee('EHT-HYD-101')
            ->assertSee('Showing')
            ->assertSee('11')
            // Pagination to page 2 displays older module EHT-EXT-008
            ->set('paginators.page', 2)
            ->assertSee('EHT-EXT-008')
            // Search filter + active chip
            ->set('search', 'Bio-Retention')
            ->assertSee('EHT-WQ-105')
            ->assertSee('Search: "Bio-Retention"', false)
            ->assertSee('Clear filters')
            ->assertDontSee('EHT-HYD-101')
            // Clear filters restores default state
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('category', '')
            ->assertSet('availability', 'all')
            ->assertSet('sort', 'newest')
            ->assertSee('EHT-HYD-101')
            // Category filter
            ->set('category', 'river-basin-flood-modelling')
            ->assertSee('EHT-FLD-201')
            ->assertDontSee('EHT-HYD-101')
            ->call('resetFilters')
            // Availability filter (session_only)
            ->set('availability', 'session_only')
            ->assertSee('EHT-WQ-105')
            ->assertSee('Physical Session Only')
            ->assertDontSee('EHT-HYD-101')
            ->call('resetFilters')
            // Sort by lowest price puts EHT-EXT-001 (RM 325.00) ahead
            ->set('sort', 'price_asc')
            ->assertSee('EHT-EXT-001')
            ->assertSee('Sort: Lowest Price')
            // Empty result state with Clear Filters button
            ->set('search', 'NonExistentKeyword999')
            ->assertSee('No matching training programmes')
            ->assertSee('Clear Filters');
    }

    public function test_catalogue_views_use_primary_blue_neutrals_and_no_prohibited_decorative_colours(): void
    {
        $paths = [
            resource_path('views/public/catalogue.blade.php'),
            resource_path('views/livewire/public/training-catalogue.blade.php'),
            resource_path('views/components/eht/module-card.blade.php'),
        ];

        foreach ($paths as $path) {
            $contents = File::get($path);

            $this->assertDoesNotMatchRegularExpression(
                '/\b(eht-teal|eht-emerald|teal-|emerald-|green-|lime-|indigo-|purple-|violet-|D-005|D-018|D-024)/i',
                $contents,
                "Found prohibited colour token or internal reference in {$path}",
            );
        }
    }
}
