<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_materials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('training_session_id')->constrained('training_sessions')->restrictOnDelete()->restrictOnUpdate();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('material_type', 30)->default('document');
            $table->string('storage_disk', 30)->default('session-materials');
            $table->string('storage_key', 500);
            $table->string('original_filename', 255);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->char('checksum_sha256', 64)->nullable();
            $table->boolean('is_downloadable')->default(true);
            $table->string('status', 30)->default('pending_review');
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete()->restrictOnUpdate();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['training_session_id', 'status', 'created_at']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE session_materials ADD CONSTRAINT chk_session_materials_status CHECK (status IN ('pending_review', 'published', 'rejected', 'archived'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('session_materials');
    }
};
