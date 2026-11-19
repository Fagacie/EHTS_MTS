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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_number', 50)->unique('uq_certificates_number');
            $table->char('verification_token', 64)->unique('uq_certificates_token');
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('record_type', 20);
            $table->foreignId('training_session_id')
                ->nullable()
                ->constrained('training_sessions')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('module_id')
                ->constrained('modules')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('booking_id')
                ->nullable()
                ->constrained('bookings')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('module_entitlement_id')
                ->nullable()
                ->constrained('module_entitlements')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('status', 20)->default('issued');
            $table->string('active_record_key', 60)
                ->virtualAs("IF(status IN ('pending', 'issued'), CONCAT(record_type, ':', IF(record_type = 'session', training_session_id, module_id)), NULL)")
                ->nullable();
            $table->string('trainee_name_snapshot', 150);
            $table->string('training_code_snapshot', 50)->nullable();
            $table->string('training_title_snapshot', 255);
            $table->date('completion_date');
            $table->dateTime('issued_at');
            $table->string('issuing_organisation_snapshot', 150);
            $table->string('signatory_name_snapshot', 150);
            $table->string('signatory_title_snapshot', 150);
            $table->string('pdf_storage_disk', 30)->default('r2');
            $table->string('pdf_storage_key', 500)->nullable();
            $table->char('pdf_checksum_sha256', 64)->nullable();
            $table->unsignedBigInteger('pdf_file_size_bytes')->nullable();
            $table->foreignId('issued_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('revoked_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->dateTime('revoked_at')->nullable();
            $table->text('revocation_reason')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['user_id', 'active_record_key'], 'uq_certificates_active_user_record');
            $table->index(['user_id', 'status', 'issued_at'], 'idx_certificates_user');
            $table->index(['record_type', 'training_session_id', 'module_id'], 'idx_certificates_training_record');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE certificates ADD CONSTRAINT chk_certificates_record_type CHECK (record_type IN ('session', 'module'))");
            DB::statement("ALTER TABLE certificates ADD CONSTRAINT chk_certificates_record_alignment CHECK ((record_type = 'session' AND training_session_id IS NOT NULL) OR (record_type = 'module' AND training_session_id IS NULL))");
            DB::statement("ALTER TABLE certificates ADD CONSTRAINT chk_certificates_status CHECK (status IN ('pending', 'issued', 'revoked'))");
            DB::statement("ALTER TABLE certificates ADD CONSTRAINT chk_certificates_revoked CHECK (status != 'revoked' OR (revoked_by IS NOT NULL AND revoked_at IS NOT NULL AND revocation_reason IS NOT NULL))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
