<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add cover image columns directly to training_sessions and make module_id nullable
        Schema::table('training_sessions', function (Blueprint $table): void {
            if (! Schema::hasColumn('training_sessions', 'cover_image_disk')) {
                $table->string('cover_image_disk', 30)->nullable()->after('description');
            }
            if (! Schema::hasColumn('training_sessions', 'cover_image_path')) {
                $table->string('cover_image_path', 500)->nullable()->after('cover_image_disk');
            }
        });

        // Backfill cover image from linked compatibility module before decoupling
        $sessionsWithModules = DB::table('training_sessions')
            ->whereNotNull('module_id')
            ->get(['id', 'module_id', 'cover_image_path']);

        foreach ($sessionsWithModules as $sessionRow) {
            if (! empty($sessionRow->cover_image_path)) {
                continue;
            }

            $moduleRow = DB::table('modules')
                ->where('id', $sessionRow->module_id)
                ->first(['cover_image_disk', 'cover_image_path']);

            if ($moduleRow && ! empty($moduleRow->cover_image_path)) {
                DB::table('training_sessions')
                    ->where('id', $sessionRow->id)
                    ->update([
                        'cover_image_disk' => $moduleRow->cover_image_disk ?: 'public',
                        'cover_image_path' => $moduleRow->cover_image_path,
                    ]);
            }
        }

        Schema::table('training_sessions', function (Blueprint $table): void {
            $table->dropForeign(['module_id']);
        });

        Schema::table('training_sessions', function (Blueprint $table): void {
            $table->unsignedBigInteger('module_id')->nullable()->change();
            $table->foreign('module_id')
                ->references('id')
                ->on('modules')
                ->nullOnDelete()
                ->restrictOnUpdate();
        });

        // 2. Make modules.category_id nullable so standalone modules do not require categories
        Schema::table('modules', function (Blueprint $table): void {
            $table->dropForeign(['category_id']);
        });

        Schema::table('modules', function (Blueprint $table): void {
            $table->unsignedBigInteger('category_id')->nullable()->change();
            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->nullOnDelete()
                ->restrictOnUpdate();
        });

        // 3. Make certificates.module_id nullable so session certificates do not require a module
        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropForeign(['module_id']);
        });

        Schema::table('certificates', function (Blueprint $table): void {
            $table->unsignedBigInteger('module_id')->nullable()->change();
            $table->foreign('module_id')
                ->references('id')
                ->on('modules')
                ->restrictOnDelete()
                ->restrictOnUpdate();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE certificates DROP CONSTRAINT chk_certificates_record_alignment');
            DB::statement("ALTER TABLE certificates ADD CONSTRAINT chk_certificates_record_alignment CHECK (
                (record_type = 'session' AND training_session_id IS NOT NULL)
                OR (record_type = 'module' AND training_session_id IS NULL AND module_id IS NOT NULL)
            )");
        }

        // 4. Add sort_order to session_materials if not present
        Schema::table('session_materials', function (Blueprint $table): void {
            if (! Schema::hasColumn('session_materials', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('is_downloadable');
            }
        });

        // 5. Detach synthetic compatibility modules (category slug = 'training-sessions') that have no standalone dependencies
        $syntheticCategoryIds = DB::table('categories')
            ->where('slug', 'training-sessions')
            ->pluck('id')
            ->all();

        if (! empty($syntheticCategoryIds)) {
            $candidateModules = DB::table('modules')
                ->whereIn('category_id', $syntheticCategoryIds)
                ->get(['id']);

            foreach ($candidateModules as $candidate) {
                $moduleId = $candidate->id;

                // Detach session certificates from synthetic compatibility module
                DB::table('certificates')
                    ->where('record_type', 'session')
                    ->where('module_id', $moduleId)
                    ->update(['module_id' => null]);

                // Detach training sessions from synthetic compatibility module
                DB::table('training_sessions')
                    ->where('module_id', $moduleId)
                    ->update(['module_id' => null]);

                $hasDependencies = DB::table('module_entitlements')->where('module_id', $moduleId)->exists()
                    || DB::table('order_items')->where('module_id', $moduleId)->exists()
                    || DB::table('module_materials')->where('module_id', $moduleId)->exists()
                    || DB::table('module_videos')->where('module_id', $moduleId)->exists()
                    || DB::table('certificates')->where('module_id', $moduleId)->exists()
                    || DB::table('announcements')->where('module_id', $moduleId)->exists();

                if (! $hasDependencies) {
                    DB::table('modules')
                        ->where('id', $moduleId)
                        ->update([
                            'status' => 'archived',
                            'archived_at' => now(),
                            'deleted_at' => now(),
                        ]);
                }
            }

            DB::table('categories')
                ->whereIn('id', $syntheticCategoryIds)
                ->update(['is_active' => false]);
        }

        // 6. Retire session-owned independent document flags on sessions without historical document entitlements
        $sessionsWithIndependentFlags = DB::table('training_sessions')
            ->where(function ($q): void {
                $q->where('is_available_independently', true)
                    ->orWhereNotNull('main_document_material_id')
                    ->orWhereNotNull('independent_price');
            })
            ->get(['id']);

        foreach ($sessionsWithIndependentFlags as $sessionRow) {
            $hasDocEntitlements = DB::table('session_document_entitlements')
                ->where('training_session_id', $sessionRow->id)
                ->exists();

            if (! $hasDocEntitlements) {
                DB::table('training_sessions')
                    ->where('id', $sessionRow->id)
                    ->update([
                        'is_available_independently' => false,
                        'independent_price' => null,
                        'main_document_material_id' => null,
                    ]);

                DB::table('session_materials')
                    ->where('training_session_id', $sessionRow->id)
                    ->where('is_main_document', true)
                    ->update(['is_main_document' => false]);
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE certificates DROP CONSTRAINT chk_certificates_record_alignment');
            DB::statement("ALTER TABLE certificates ADD CONSTRAINT chk_certificates_record_alignment CHECK (
                (record_type = 'session' AND training_session_id IS NOT NULL)
                OR (record_type = 'module' AND training_session_id IS NULL)
            )");
        }

        Schema::table('session_materials', function (Blueprint $table): void {
            if (Schema::hasColumn('session_materials', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
        });

        Schema::table('training_sessions', function (Blueprint $table): void {
            if (Schema::hasColumn('training_sessions', 'cover_image_path')) {
                $table->dropColumn(['cover_image_disk', 'cover_image_path']);
            }
        });
    }
};
