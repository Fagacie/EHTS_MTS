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
        Schema::create('instructor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->unique('uq_instructor_profiles_user')
                ->constrained('users')
                ->cascadeOnDelete()
                ->restrictOnUpdate();
            $table->string('display_name', 150)->nullable();
            $table->string('professional_title', 150)->nullable();
            $table->string('qualifications', 255)->nullable();
            $table->string('short_bio', 500)->nullable();
            $table->text('biography')->nullable();
            $table->json('specialisations')->nullable();
            $table->string('photo_storage_disk', 30)->nullable();
            $table->string('photo_storage_key', 500)->nullable();
            $table->boolean('is_public')->default(false);
            $table->dateTime('published_at')->nullable();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->restrictOnUpdate();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('is_public', 'idx_instructor_profiles_public');
        });

        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')
                ->constrained('modules')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('session_code', 50)->unique('uq_sessions_code');
            $table->string('title', 200);
            $table->string('slug', 220)->unique('uq_sessions_slug');
            $table->longText('description')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedInteger('duration_minutes');
            $table->string('timezone', 64)->default('Asia/Kuala_Lumpur');
            $table->string('venue_name', 200)->nullable();
            $table->text('venue_address')->nullable();
            $table->string('venue_city', 100)->nullable();
            $table->string('venue_state', 100)->nullable();
            $table->string('venue_postcode', 20)->nullable();
            $table->string('venue_map_url', 500)->nullable();
            $table->text('venue_notes')->nullable();
            $table->unsignedInteger('capacity');
            $table->unsignedInteger('reserved_seats_count')->default(0);
            $table->unsignedInteger('confirmed_seats_count')->default(0);
            $table->dateTime('registration_opens_at')->nullable();
            $table->dateTime('registration_closes_at');
            $table->decimal('price', 12, 2);
            $table->char('currency', 3)->default('MYR');
            $table->string('recording_access_policy', 30)->default('attendees_only');
            $table->string('status', 30)->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->text('completion_notes')->nullable();
            $table->foreignId('completion_submitted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->restrictOnUpdate();
            $table->dateTime('completion_submitted_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('completed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->restrictOnUpdate();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->restrictOnUpdate();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->restrictOnUpdate();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['id', 'module_id'], 'uq_sessions_id_module');
            $table->index(['module_id', 'status', 'starts_at'], 'idx_sessions_module_status');
            $table->index(['status', 'starts_at', 'registration_closes_at'], 'idx_sessions_upcoming');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE training_sessions ADD CONSTRAINT chk_sessions_duration CHECK (duration_minutes > 0)");
            DB::statement("ALTER TABLE training_sessions ADD CONSTRAINT chk_sessions_capacity_min CHECK (capacity >= 1)");
            DB::statement("ALTER TABLE training_sessions ADD CONSTRAINT chk_sessions_price CHECK (price >= 0)");
            DB::statement("ALTER TABLE training_sessions ADD CONSTRAINT chk_sessions_rec_policy CHECK (recording_access_policy IN ('attendees_only', 'none'))");
            DB::statement("ALTER TABLE training_sessions ADD CONSTRAINT chk_sessions_status CHECK (status IN ('draft', 'published', 'full', 'registration_closed', 'in_progress', 'completed', 'cancelled'))");
            DB::statement("ALTER TABLE training_sessions ADD CONSTRAINT chk_sessions_dates CHECK (ends_at > starts_at)");
            DB::statement("ALTER TABLE training_sessions ADD CONSTRAINT chk_sessions_reg_close CHECK (registration_closes_at < starts_at)");
            DB::statement("ALTER TABLE training_sessions ADD CONSTRAINT chk_sessions_reg_window CHECK (registration_opens_at IS NULL OR registration_opens_at < registration_closes_at)");
            DB::statement("ALTER TABLE training_sessions ADD CONSTRAINT chk_sessions_capacity CHECK (reserved_seats_count + confirmed_seats_count <= capacity)");
        }

        Schema::create('session_instructors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')
                ->constrained('training_sessions')
                ->cascadeOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('instructor_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('role_label', 50)->nullable()->default('lead');
            $table->foreignId('assigned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->restrictOnUpdate();
            $table->dateTime('assigned_at')->useCurrent();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['training_session_id', 'instructor_id'], 'uq_session_instructors_pair');
            $table->index(['instructor_id', 'training_session_id'], 'idx_session_instructors_user');
        });

        Schema::create('session_recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')
                ->constrained('training_sessions')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('provider', 30)->default('bunny_stream');
            $table->string('provider_library_id', 100);
            $table->string('provider_video_id', 100);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('thumbnail_url', 500)->nullable();
            $table->string('encoding_status', 20)->default('pending');
            $table->string('status', 30)->default('pending_review');
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('submitted_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->dateTime('submitted_at')->useCurrent();
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->restrictOnUpdate();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->dateTime('deleted_at')->nullable();

            $table->unique(['provider', 'provider_library_id', 'provider_video_id'], 'uq_session_recordings_provider');
            $table->index(['training_session_id', 'status', 'sort_order', 'deleted_at'], 'idx_session_recordings_session');
            $table->index(['status', 'submitted_at'], 'idx_session_recordings_review');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE session_recordings ADD CONSTRAINT chk_session_recordings_provider CHECK (provider IN ('bunny_stream'))");
            DB::statement("ALTER TABLE session_recordings ADD CONSTRAINT chk_session_recordings_encoding CHECK (encoding_status IN ('pending', 'processing', 'ready', 'failed'))");
            DB::statement("ALTER TABLE session_recordings ADD CONSTRAINT chk_session_recordings_status CHECK (status IN ('draft', 'pending_review', 'published', 'rejected', 'archived'))");
        }

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 40)->unique('uq_orders_number');
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('order_type', 30);
            $table->string('status', 30)->default('pending_payment');
            $table->char('currency', 3)->default('MYR');
            $table->decimal('subtotal_amount', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->string('customer_name', 150);
            $table->string('customer_email', 255);
            $table->string('customer_phone', 30)->nullable();
            $table->string('customer_type', 20)->default('individual');
            $table->string('company_name', 200)->nullable();
            $table->string('company_registration_no', 50)->nullable();
            $table->string('billing_contact_name', 150)->nullable();
            $table->string('billing_email', 255)->nullable();
            $table->string('billing_phone', 30)->nullable();
            $table->string('billing_address_line_1', 255)->nullable();
            $table->string('billing_address_line_2', 255)->nullable();
            $table->string('billing_city', 100)->nullable();
            $table->string('billing_state', 100)->nullable();
            $table->string('billing_postcode', 20)->nullable();
            $table->char('billing_country', 2)->nullable()->default('MY');
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('refunded_at')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['id', 'order_type'], 'uq_orders_id_type');
            $table->unique(['id', 'user_id'], 'uq_orders_id_user');
            $table->index(['user_id', 'created_at'], 'idx_orders_user');
            $table->index(['status', 'order_type', 'created_at'], 'idx_orders_status_type');
            $table->index(['status', 'expires_at'], 'idx_orders_expiry');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders ADD CONSTRAINT chk_orders_type CHECK (order_type IN ('session_booking', 'module_purchase'))");
            DB::statement("ALTER TABLE orders ADD CONSTRAINT chk_orders_status CHECK (status IN ('pending_payment', 'paid', 'failed', 'cancelled', 'expired', 'refunded', 'partially_refunded'))");
            DB::statement("ALTER TABLE orders ADD CONSTRAINT chk_orders_amounts CHECK (subtotal_amount >= 0 AND total_amount >= 0)");
            DB::statement("ALTER TABLE orders ADD CONSTRAINT chk_orders_customer_type CHECK (customer_type IN ('individual', 'company'))");
        }

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->constrained('orders')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('order_type', 30);
            $table->string('item_type', 30);
            $table->foreignId('training_session_id')
                ->nullable()
                ->constrained('training_sessions')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('module_id')
                ->nullable()
                ->constrained('modules')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('item_code_snapshot', 50);
            $table->string('item_title_snapshot', 255);
            $table->decimal('unit_price', 12, 2);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('line_total', 12, 2);
            $table->char('currency', 3)->default('MYR');
            $table->json('metadata_snapshot')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['order_id', 'order_type'], 'idx_order_items_order');
            $table->unique(['id', 'order_id', 'training_session_id'], 'uq_order_items_id_order_session');
            $table->unique(['id', 'order_id', 'module_id'], 'uq_order_items_id_order_module');
            $table->index('training_session_id', 'idx_order_items_session');
            $table->index('module_id', 'idx_order_items_module');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE order_items ADD CONSTRAINT fk_order_items_order_type FOREIGN KEY (order_id, order_type) REFERENCES orders (id, order_type) ON DELETE RESTRICT ON UPDATE RESTRICT");
            DB::statement("ALTER TABLE order_items ADD CONSTRAINT chk_order_items_qty CHECK (quantity = 1 AND unit_price >= 0 AND line_total >= 0 AND line_total = unit_price * quantity)");
            DB::statement("ALTER TABLE order_items ADD CONSTRAINT chk_order_items_type_alignment CHECK ((order_type = 'session_booking' AND item_type = 'training_session' AND training_session_id IS NOT NULL AND module_id IS NULL) OR (order_type = 'module_purchase' AND item_type = 'module' AND module_id IS NOT NULL AND training_session_id IS NULL))");
        }

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference', 40)->unique('uq_bookings_reference');
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('training_session_id')
                ->constrained('training_sessions')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('order_id')
                ->constrained('orders')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('order_item_id')
                ->unique('uq_bookings_order_item')
                ->constrained('order_items')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('status', 30)->default('pending_payment');
            $table->tinyInteger('is_active_booking')
                ->virtualAs("IF(status IN ('pending_payment', 'confirmed'), 1, NULL)")
                ->nullable();
            $table->dateTime('reserved_at')->useCurrent();
            $table->dateTime('reservation_expires_at')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('expired_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('refunded_at')->nullable();
            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->restrictOnUpdate();
            $table->text('cancellation_reason')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['user_id', 'training_session_id', 'is_active_booking'], 'uq_bookings_active_user_session');
            $table->unique(['id', 'training_session_id', 'user_id'], 'uq_bookings_composite');
            $table->unique(['id', 'order_id', 'user_id'], 'uq_bookings_id_order_user');
            $table->index(['training_session_id', 'status', 'reservation_expires_at'], 'idx_bookings_session_status');
            $table->index(['user_id', 'status', 'created_at'], 'idx_bookings_user');
            $table->index(['status', 'reservation_expires_at'], 'idx_bookings_expiry');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE bookings ADD CONSTRAINT chk_bookings_status CHECK (status IN ('pending_payment', 'confirmed', 'expired', 'cancelled', 'refunded'))");
            DB::statement("ALTER TABLE bookings ADD CONSTRAINT fk_bookings_order_user FOREIGN KEY (order_id, user_id) REFERENCES orders (id, user_id) ON DELETE RESTRICT ON UPDATE RESTRICT");
            DB::statement("ALTER TABLE bookings ADD CONSTRAINT fk_bookings_order_item_session FOREIGN KEY (order_item_id, order_id, training_session_id) REFERENCES order_items (id, order_id, training_session_id) ON DELETE RESTRICT ON UPDATE RESTRICT");
        }

        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')
                ->constrained('training_sessions')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('booking_id')
                ->unique('uq_attendance_booking')
                ->constrained('bookings')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('attendance_status', 20)->default('pending');
            $table->string('review_status', 30)->default('draft');
            $table->dateTime('check_in_at')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->dateTime('recorded_at')->nullable();
            $table->foreignId('submitted_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->dateTime('submitted_at')->nullable();
            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->dateTime('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['id', 'training_session_id', 'booking_id', 'user_id'], 'uq_attendance_composite');
            $table->index(['training_session_id', 'review_status', 'attendance_status'], 'idx_attendance_session_review');
            $table->index(['user_id', 'attendance_status', 'review_status'], 'idx_attendance_user');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE attendance ADD CONSTRAINT fk_attendance_booking_composite FOREIGN KEY (booking_id, training_session_id, user_id) REFERENCES bookings (id, training_session_id, user_id) ON DELETE RESTRICT ON UPDATE RESTRICT");
            DB::statement("ALTER TABLE attendance ADD CONSTRAINT chk_attendance_status CHECK (attendance_status IN ('pending', 'present', 'absent', 'excused'))");
            DB::statement("ALTER TABLE attendance ADD CONSTRAINT chk_attendance_review_status CHECK (review_status IN ('draft', 'submitted', 'verified', 'correction_requested'))");
            DB::statement("ALTER TABLE attendance ADD CONSTRAINT chk_attendance_marked_before_submit CHECK (review_status IN ('draft', 'correction_requested') OR (attendance_status IN ('present', 'absent', 'excused') AND recorded_by IS NOT NULL AND recorded_at IS NOT NULL AND submitted_by IS NOT NULL AND submitted_at IS NOT NULL))");
            DB::statement("ALTER TABLE attendance ADD CONSTRAINT chk_attendance_verified_meta CHECK (review_status != 'verified' OR (verified_by IS NOT NULL AND verified_at IS NOT NULL))");
        }

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 100)->unique('uq_system_settings_key');
            $table->json('setting_value')->nullable();
            $table->string('value_type', 20)->default('string');
            $table->string('setting_group', 50)->default('general')->index('idx_system_settings_group');
            $table->string('description', 255)->nullable();
            $table->boolean('is_public')->default(false);
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->restrictOnUpdate();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE system_settings ADD CONSTRAINT chk_system_settings_type CHECK (value_type IN ('string', 'integer', 'boolean', 'json'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('attendance');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('session_recordings');
        Schema::dropIfExists('session_instructors');
        Schema::dropIfExists('training_sessions');
        Schema::dropIfExists('instructor_profiles');
    }
};
