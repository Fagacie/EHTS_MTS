<?php

namespace Tests\Feature;

use App\Models\TrainingSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SessionCentredAdministratorMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_training_session_has_session_owned_independent_purchase_fields(): void
    {
        $this->assertTrue(Schema::hasColumn('training_sessions', 'is_available_independently'));
        $this->assertTrue(Schema::hasColumn('training_sessions', 'independent_price'));
        $this->assertTrue(Schema::hasColumn('training_sessions', 'main_document_material_id'));
        $this->assertTrue(Schema::hasColumn('session_materials', 'is_main_document'));

        $casts = (new TrainingSession())->getCasts();
        $this->assertSame('boolean', $casts['is_available_independently']);
        $this->assertSame('decimal:2', $casts['independent_price']);
    }
}
