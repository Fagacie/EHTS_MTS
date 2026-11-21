<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->foreignId('approved_by')
                ->nullable()
                ->after('issued_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->dateTime('approved_at')->nullable()->after('approved_by');
            $table->string('pdf_generation_status', 20)->default('queued')->after('pdf_file_size_bytes');
            $table->unsignedSmallInteger('pdf_generation_attempts')->default(0)->after('pdf_generation_status');
            $table->text('pdf_generation_error')->nullable()->after('pdf_generation_attempts');
        });

        Schema::table('certificates', function (Blueprint $table): void {
            $table->dateTime('issued_at')->nullable()->change();
            $table->foreignId('issued_by')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'approved_by',
                'approved_at',
                'pdf_generation_status',
                'pdf_generation_attempts',
                'pdf_generation_error',
            ]);
        });
    }
};
