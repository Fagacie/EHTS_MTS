<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Models\Module;
use App\Models\TrainingSession;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SessionCentredAdministratorMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_training_sessions_and_standalone_modules_are_decoupled_in_schema(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $this->assertTrue(Schema::hasColumn('training_sessions', 'cover_image_disk'));
        $this->assertTrue(Schema::hasColumn('training_sessions', 'cover_image_path'));

        $admin = User::factory()->create();
        $admin->assignRole(RoleSlug::Administrator);

        $session = TrainingSession::query()->create([
            'module_id' => null,
            'session_code' => 'SES-DECOUPLED-01',
            'title' => 'Independent Physical Training Session',
            'slug' => 'independent-physical-training-session',
            'description' => 'Physical training session with no module dependency.',
            'starts_at' => now()->addDays(14),
            'ends_at' => now()->addDays(14)->addHours(8),
            'duration_minutes' => 480,
            'timezone' => 'Asia/Kuala_Lumpur',
            'registration_closes_at' => now()->addDays(10),
            'venue_name' => 'Shah Alam Training Hall',
            'capacity' => 20,
            'price' => '650.00',
            'status' => 'published',
            'created_by' => $admin->id,
        ]);

        $module = Module::query()->create([
            'category_id' => null,
            'code' => 'MOD-DECOUPLED-01',
            'title' => 'Independent Self-Paced Module',
            'slug' => 'independent-self-paced-module',
            'description' => 'Self-paced standalone module without category requirement.',
            'is_available_independently' => true,
            'independent_price' => '450.00',
            'status' => 'published',
            'published_at' => now(),
            'created_by' => $admin->id,
        ]);

        $this->assertNull($session->module_id);
        $this->assertNull($module->category_id);
        $this->assertTrue(TrainingSession::query()->publicVisible()->whereKey($session->id)->exists());
        $this->assertTrue(Module::query()->published()->whereKey($module->id)->exists());
    }
}
