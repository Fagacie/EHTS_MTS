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
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->char('delivery_key', 36)->unique('uq_notification_deliveries_key');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->restrictOnUpdate();
            $table->string('recipient_email', 255);
            $table->string('event_type', 80);
            $table->string('channel', 30)->default('mail');
            $table->string('mailer', 50)->nullable();
            $table->string('subject', 255);
            $table->string('status', 20)->default('queued');
            $table->string('provider_message_id', 191)->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('queued_at')->useCurrent();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['user_id', 'status', 'created_at'], 'idx_notification_deliveries_user');
            $table->index(['event_type', 'status', 'created_at'], 'idx_notification_deliveries_event');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE notification_deliveries ADD CONSTRAINT chk_notification_deliveries_status CHECK (status IN ('queued', 'sent', 'failed'))");
        }

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->longText('body');
            $table->string('audience_type', 30)->default('all_customers');
            $table->foreignId('training_session_id')
                ->nullable()
                ->constrained('training_sessions')
                ->nullOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('module_id')
                ->nullable()
                ->constrained('modules')
                ->nullOnDelete()
                ->restrictOnUpdate();
            $table->string('target_role', 50)->nullable();
            $table->boolean('send_email')->default(true);
            $table->boolean('send_in_app')->default(true);
            $table->string('status', 20)->default('draft');
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['status', 'scheduled_at'], 'idx_announcements_status');
            $table->index('training_session_id', 'idx_announcements_session');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE announcements ADD CONSTRAINT chk_announcements_audience CHECK (audience_type IN ('selected_users', 'session_participants', 'module_entitled_users', 'role', 'all_customers'))");
            DB::statement("ALTER TABLE announcements ADD CONSTRAINT chk_announcements_status CHECK (status IN ('draft', 'scheduled', 'sending', 'sent', 'failed'))");
        }

        Schema::create('announcement_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')
                ->constrained('announcements')
                ->cascadeOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->restrictOnUpdate();
            $table->string('delivery_status', 20)->default('pending');
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('read_at')->nullable();
            $table->text('error_message')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['announcement_id', 'user_id'], 'uq_announcement_recipients_pair');
            $table->index(['user_id', 'read_at'], 'idx_announcement_recipients_user');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE announcement_recipients ADD CONSTRAINT chk_announcement_recipients_status CHECK (delivery_status IN ('pending', 'sent', 'failed'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcement_recipients');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('notification_deliveries');
    }
};
