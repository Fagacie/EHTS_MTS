<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('module_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')
                ->constrained('modules')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('provider', 30)->default('bunny_stream');
            $table->string('provider_library_id', 100);
            $table->string('provider_video_id', 100);
            $table->string('provider_collection_id', 100)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('thumbnail_url', 500)->nullable();
            $table->string('encoding_status', 20)->default('pending');
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->dateTime('deleted_at')->nullable();

            $table->unique(['provider', 'provider_library_id', 'provider_video_id'], 'uq_module_videos_provider');
            $table->index(['module_id', 'status', 'sort_order', 'deleted_at'], 'idx_module_videos_module');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE module_videos ADD CONSTRAINT chk_module_videos_provider CHECK (provider IN ('bunny_stream'))");
            DB::statement("ALTER TABLE module_videos ADD CONSTRAINT chk_module_videos_encoding CHECK (encoding_status IN ('pending', 'processing', 'ready', 'failed'))");
            DB::statement("ALTER TABLE module_videos ADD CONSTRAINT chk_module_videos_status CHECK (status IN ('draft', 'published', 'archived'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_videos');
    }
};
