<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE module_materials MODIFY COLUMN storage_disk VARCHAR(30) NOT NULL DEFAULT 'materials'");
            DB::statement("ALTER TABLE certificates MODIFY COLUMN pdf_storage_disk VARCHAR(30) NOT NULL DEFAULT 'certificates'");
        }

        DB::table('module_materials')
            ->where('storage_disk', 'r2')
            ->update([
                'storage_disk' => 'materials',
                'storage_bucket' => 'etms-server-storage',
            ]);

        DB::table('certificates')
            ->where('pdf_storage_disk', 'r2')
            ->update(['pdf_storage_disk' => 'certificates']);

        Schema::table('module_videos', function (Blueprint $table): void {
            $table->string('storage_disk', 30)->default('videos')->after('provider_collection_id');
            $table->string('source_storage_key', 500)->nullable()->after('storage_disk');
            $table->string('hls_manifest_key', 500)->nullable()->after('source_storage_key');
            $table->string('original_filename', 255)->nullable()->after('hls_manifest_key');
            $table->unsignedBigInteger('file_size_bytes')->nullable()->after('original_filename');
            $table->string('mime_type', 120)->nullable()->after('file_size_bytes');
            $table->text('processing_error')->nullable()->after('encoding_status');
        });

        Schema::table('session_recordings', function (Blueprint $table): void {
            $table->string('storage_disk', 30)->default('recordings')->after('provider_video_id');
            $table->string('source_storage_key', 500)->nullable()->after('storage_disk');
            $table->string('hls_manifest_key', 500)->nullable()->after('source_storage_key');
            $table->string('original_filename', 255)->nullable()->after('hls_manifest_key');
            $table->unsignedBigInteger('file_size_bytes')->nullable()->after('original_filename');
            $table->string('mime_type', 120)->nullable()->after('file_size_bytes');
            $table->text('processing_error')->nullable()->after('encoding_status');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE module_videos DROP CHECK chk_module_videos_provider');
            DB::statement("ALTER TABLE module_videos MODIFY COLUMN provider VARCHAR(30) NOT NULL DEFAULT 'local_hls'");
            DB::statement("ALTER TABLE module_videos MODIFY COLUMN provider_library_id VARCHAR(100) NOT NULL DEFAULT 'local'");
            DB::statement("ALTER TABLE module_videos ADD CONSTRAINT chk_module_videos_provider CHECK (provider IN ('local_hls', 'bunny_stream'))");

            DB::statement('ALTER TABLE session_recordings DROP CHECK chk_session_recordings_provider');
            DB::statement("ALTER TABLE session_recordings MODIFY COLUMN provider VARCHAR(30) NOT NULL DEFAULT 'local_hls'");
            DB::statement("ALTER TABLE session_recordings MODIFY COLUMN provider_library_id VARCHAR(100) NOT NULL DEFAULT 'local'");
            DB::statement("ALTER TABLE session_recordings ADD CONSTRAINT chk_session_recordings_provider CHECK (provider IN ('local_hls', 'bunny_stream'))");
        }

        DB::table('module_videos')
            ->where('provider', 'bunny_stream')
            ->update([
                'provider' => 'local_hls',
                'provider_library_id' => 'local',
                'storage_disk' => 'videos',
            ]);

        DB::table('session_recordings')
            ->where('provider', 'bunny_stream')
            ->update([
                'provider' => 'local_hls',
                'provider_library_id' => 'local',
                'storage_disk' => 'recordings',
            ]);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE session_recordings DROP CHECK chk_session_recordings_provider');
            DB::statement("ALTER TABLE session_recordings ADD CONSTRAINT chk_session_recordings_provider CHECK (provider IN ('bunny_stream'))");

            DB::statement('ALTER TABLE module_videos DROP CHECK chk_module_videos_provider');
            DB::statement("ALTER TABLE module_videos ADD CONSTRAINT chk_module_videos_provider CHECK (provider IN ('bunny_stream'))");
        }

        Schema::table('session_recordings', function (Blueprint $table): void {
            $table->dropColumn([
                'storage_disk',
                'source_storage_key',
                'hls_manifest_key',
                'original_filename',
                'file_size_bytes',
                'mime_type',
                'processing_error',
            ]);
        });

        Schema::table('module_videos', function (Blueprint $table): void {
            $table->dropColumn([
                'storage_disk',
                'source_storage_key',
                'hls_manifest_key',
                'original_filename',
                'file_size_bytes',
                'mime_type',
                'processing_error',
            ]);
        });
    }
};
