<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_sessions', function (Blueprint $table): void {
            $table->boolean('is_available_independently')->default(false)->after('recording_access_policy');
            $table->decimal('independent_price', 12, 2)->nullable()->after('is_available_independently');
            $table->foreignId('main_document_material_id')
                ->nullable()
                ->after('independent_price')
                ->constrained('session_materials')
                ->restrictOnDelete()
                ->restrictOnUpdate();
        });

        Schema::table('session_materials', function (Blueprint $table): void {
            $table->boolean('is_main_document')->default(false)->after('is_downloadable');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE training_sessions ADD CONSTRAINT chk_sessions_independent_price CHECK (independent_price IS NULL OR independent_price >= 0)');
            DB::statement('ALTER TABLE training_sessions ADD CONSTRAINT chk_sessions_independent_purchase CHECK (is_available_independently = FALSE OR independent_price IS NOT NULL)');
        }
    }

    public function down(): void
    {
        Schema::table('training_sessions', function (Blueprint $table): void {
            $table->dropForeign(['main_document_material_id']);
            $table->dropColumn(['main_document_material_id', 'independent_price', 'is_available_independently']);
        });

        Schema::table('session_materials', function (Blueprint $table): void {
            $table->dropColumn('is_main_document');
        });
    }
};
