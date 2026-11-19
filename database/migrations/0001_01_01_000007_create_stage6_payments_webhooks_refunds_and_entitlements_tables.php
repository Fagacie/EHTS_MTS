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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_reference', 40)->unique('uq_payments_reference');
            $table->foreignId('order_id')
                ->constrained('orders')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('gateway', 30)->default('billplz');
            $table->string('payment_method', 50)->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('refunded_amount', 12, 2)->default(0.00);
            $table->char('currency', 3)->default('MYR');
            $table->string('status', 30)->default('pending');
            $table->string('gateway_bill_id', 100)->nullable();
            $table->string('gateway_transaction_id', 100)->nullable();
            $table->string('gateway_reference_1', 100)->nullable();
            $table->string('gateway_payment_url', 500)->nullable();
            $table->string('gateway_state', 50)->nullable();
            $table->json('gateway_payload')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->string('manual_reference', 100)->nullable();
            $table->string('evidence_storage_disk', 30)->nullable();
            $table->string('evidence_storage_key', 500)->nullable();
            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->restrictOnUpdate();
            $table->dateTime('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['gateway', 'gateway_bill_id'], 'uq_payments_gateway_bill');
            $table->unique(['gateway', 'gateway_transaction_id'], 'uq_payments_gateway_txn');
            $table->unique(['id', 'order_id'], 'uq_payments_id_order');
            $table->unique(['id', 'order_id', 'user_id'], 'uq_payments_id_order_user');
            $table->index(['order_id', 'status'], 'idx_payments_order');
            $table->index(['user_id', 'created_at'], 'idx_payments_user');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE payments ADD CONSTRAINT fk_payments_order_user FOREIGN KEY (order_id, user_id) REFERENCES orders (id, user_id) ON DELETE RESTRICT ON UPDATE RESTRICT');
            DB::statement("ALTER TABLE payments ADD CONSTRAINT chk_payments_gateway CHECK (gateway IN ('billplz'))");
            DB::statement('ALTER TABLE payments ADD CONSTRAINT chk_payments_amount CHECK (amount > 0)');
            DB::statement('ALTER TABLE payments ADD CONSTRAINT chk_payments_refunded_amount CHECK (refunded_amount >= 0 AND refunded_amount <= amount)');
            DB::statement("ALTER TABLE payments ADD CONSTRAINT chk_payments_status CHECK (status IN ('pending', 'successful', 'failed', 'cancelled', 'expired', 'refunded', 'partially_refunded'))");
        }

        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 30)->default('billplz');
            $table->char('idempotency_key', 64)->nullable();
            $table->char('request_fingerprint', 64);
            $table->string('gateway_bill_id', 100)->nullable();
            $table->string('gateway_collection_id', 100)->nullable();
            $table->string('gateway_transaction_id', 100)->nullable();
            $table->foreignId('payment_id')
                ->nullable()
                ->constrained('payments')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('event_type', 50)->nullable();
            $table->boolean('signature_valid')->default(false);
            $table->decimal('reported_amount', 12, 2)->nullable();
            $table->decimal('reported_paid_amount', 12, 2)->nullable();
            $table->boolean('reported_paid')->nullable();
            $table->string('reported_state', 30)->nullable();
            $table->dateTime('reported_paid_at')->nullable();
            $table->char('reported_currency', 3)->nullable();
            $table->json('headers')->nullable();
            $table->json('payload');
            $table->string('processing_status', 40)->default('received');
            $table->text('rejection_reason')->nullable();
            $table->string('source_ip', 45)->nullable();
            $table->dateTime('received_at')->useCurrent();
            $table->dateTime('processed_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['gateway', 'idempotency_key'], 'uq_webhook_gateway_idempotency');
            $table->index(['gateway', 'gateway_bill_id', 'processing_status'], 'idx_webhook_bill_status');
            $table->index(['processing_status', 'received_at'], 'idx_webhook_status');
            $table->index(['gateway', 'request_fingerprint', 'received_at'], 'idx_webhook_request_fingerprint');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE payment_webhook_events ADD CONSTRAINT fk_webhook_payment_order FOREIGN KEY (payment_id, order_id) REFERENCES payments (id, order_id) ON DELETE RESTRICT ON UPDATE RESTRICT');
            DB::statement("ALTER TABLE payment_webhook_events ADD CONSTRAINT chk_webhook_processing_status CHECK (processing_status IN ('received', 'unmatched_pending', 'processed', 'ignored_duplicate', 'rejected_invalid_signature', 'rejected_mismatch', 'failed'))");
            DB::statement('ALTER TABLE payment_webhook_events ADD CONSTRAINT chk_webhook_match_pair CHECK ((payment_id IS NULL AND order_id IS NULL) OR (payment_id IS NOT NULL AND order_id IS NOT NULL))');
            DB::statement("ALTER TABLE payment_webhook_events ADD CONSTRAINT chk_webhook_idempotency_scope CHECK ((signature_valid = TRUE AND processing_status IN ('received', 'unmatched_pending', 'processed', 'rejected_mismatch', 'failed') AND idempotency_key IS NOT NULL) OR (signature_valid = FALSE AND processing_status = 'rejected_invalid_signature' AND idempotency_key IS NULL) OR (signature_valid = TRUE AND processing_status = 'ignored_duplicate' AND idempotency_key IS NULL))");
            DB::statement("ALTER TABLE payment_webhook_events ADD CONSTRAINT chk_webhook_state_mapping CHECK ((processing_status = 'unmatched_pending' AND signature_valid = TRUE AND payment_id IS NULL AND order_id IS NULL) OR (processing_status IN ('processed', 'rejected_mismatch') AND signature_valid = TRUE AND payment_id IS NOT NULL AND order_id IS NOT NULL) OR (processing_status = 'rejected_invalid_signature' AND signature_valid = FALSE AND payment_id IS NULL AND order_id IS NULL) OR (processing_status = 'ignored_duplicate' AND signature_valid = TRUE AND payment_id IS NULL AND order_id IS NULL) OR (processing_status IN ('received', 'failed') AND ((payment_id IS NULL AND order_id IS NULL) OR (payment_id IS NOT NULL AND order_id IS NOT NULL))))");
        }

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('refund_reference', 40)->unique('uq_refunds_reference');
            $table->foreignId('order_id')
                ->constrained('orders')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('payment_id')
                ->constrained('payments')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('booking_id')
                ->nullable()
                ->constrained('bookings')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('MYR');
            $table->string('refund_method', 30)->nullable();
            $table->string('provider_disbursement_id', 100)->nullable();
            $table->string('disbursement_reference', 100)->nullable();
            $table->string('disbursement_status', 30)->nullable();
            $table->json('disbursement_payload')->nullable();
            $table->text('reason');
            $table->string('status', 30)->default('requested');
            $table->string('request_source', 20)->default('system');
            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('source_event', 100);
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('processed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->restrictOnUpdate();
            $table->dateTime('requested_at')->useCurrent();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('gateway_payload')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['order_id', 'payment_id', 'status'], 'idx_refunds_order_payment');
            $table->index(['status', 'requested_at'], 'idx_refunds_status');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE refunds ADD CONSTRAINT fk_refunds_payment_order_user FOREIGN KEY (payment_id, order_id, user_id) REFERENCES payments (id, order_id, user_id) ON DELETE RESTRICT ON UPDATE RESTRICT');
            DB::statement('ALTER TABLE refunds ADD CONSTRAINT fk_refunds_booking_order_user FOREIGN KEY (booking_id, order_id, user_id) REFERENCES bookings (id, order_id, user_id) ON DELETE RESTRICT ON UPDATE RESTRICT');
            DB::statement('ALTER TABLE refunds ADD CONSTRAINT chk_refunds_amount CHECK (amount > 0)');
            DB::statement("ALTER TABLE refunds ADD CONSTRAINT chk_refunds_method CHECK (refund_method IS NULL OR refund_method IN ('payment_order', 'manual_bank_transfer', 'other'))");
            DB::statement("ALTER TABLE refunds ADD CONSTRAINT chk_refunds_disbursement_status CHECK (disbursement_status IS NULL OR disbursement_status IN ('not_started', 'processing', 'completed', 'failed', 'cancelled'))");
            DB::statement("ALTER TABLE refunds ADD CONSTRAINT chk_refunds_status CHECK (status IN ('requested', 'approved', 'processing', 'completed', 'rejected', 'failed'))");
            DB::statement("ALTER TABLE refunds ADD CONSTRAINT chk_refunds_request_source CHECK (request_source IN ('system', 'administrator'))");
            DB::statement("ALTER TABLE refunds ADD CONSTRAINT chk_refunds_request_source_actor CHECK ((request_source = 'system' AND requested_by IS NULL) OR (request_source = 'administrator' AND requested_by IS NOT NULL))");
            DB::statement("ALTER TABLE refunds ADD CONSTRAINT chk_refunds_approval_inputs CHECK (status IN ('requested', 'rejected') OR (refund_method IS NOT NULL AND approved_by IS NOT NULL AND approved_at IS NOT NULL))");
        }

        Schema::create('module_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('module_id')
                ->constrained('modules')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('order_item_id')
                ->nullable()
                ->unique('uq_entitlements_order_item')
                ->constrained('order_items')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('grant_source', 30)->default('purchase');
            $table->string('status', 20)->default('active');
            $table->tinyInteger('is_active_entitlement')
                ->virtualAs("IF(status = 'active', 1, NULL)")
                ->nullable();
            $table->dateTime('granted_at')->useCurrent();
            $table->foreignId('granted_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->text('grant_reason')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->foreignId('revoked_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->text('revocation_reason')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['user_id', 'module_id', 'is_active_entitlement'], 'uq_entitlements_active_user_module');
            $table->index(['module_id', 'status'], 'idx_entitlements_module_status');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE module_entitlements ADD CONSTRAINT fk_entitlements_order_user FOREIGN KEY (order_id, user_id) REFERENCES orders (id, user_id) ON DELETE RESTRICT ON UPDATE RESTRICT');
            DB::statement('ALTER TABLE module_entitlements ADD CONSTRAINT fk_entitlements_order_item_module FOREIGN KEY (order_item_id, order_id, module_id) REFERENCES order_items (id, order_id, module_id) ON DELETE RESTRICT ON UPDATE RESTRICT');
            DB::statement("ALTER TABLE module_entitlements ADD CONSTRAINT chk_entitlements_grant_source CHECK (grant_source IN ('purchase', 'admin_grant'))");
            DB::statement("ALTER TABLE module_entitlements ADD CONSTRAINT chk_entitlements_status CHECK (status IN ('active', 'revoked'))");
            DB::statement("ALTER TABLE module_entitlements ADD CONSTRAINT chk_entitlements_source CHECK ((grant_source = 'purchase' AND order_id IS NOT NULL AND order_item_id IS NOT NULL) OR (grant_source = 'admin_grant' AND order_id IS NULL AND order_item_id IS NULL AND granted_by IS NOT NULL))");
            DB::statement("ALTER TABLE module_entitlements ADD CONSTRAINT chk_entitlements_revocation CHECK (status != 'revoked' OR (revoked_by IS NOT NULL AND revoked_at IS NOT NULL AND revocation_reason IS NOT NULL))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_entitlements');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('payments');
    }
};
