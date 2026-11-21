<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->foreignId('session_material_id')
                ->nullable()
                ->after('module_id')
                ->constrained('session_materials')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->index('session_material_id', 'idx_order_items_session_material');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE order_items DROP CONSTRAINT chk_order_items_type_alignment');
            DB::statement("ALTER TABLE order_items ADD CONSTRAINT chk_order_items_type_alignment CHECK (
                (order_type = 'session_booking' AND item_type = 'training_session' AND training_session_id IS NOT NULL AND module_id IS NULL AND session_material_id IS NULL)
                OR (order_type = 'module_purchase' AND item_type = 'module' AND module_id IS NOT NULL AND training_session_id IS NULL AND session_material_id IS NULL)
                OR (order_type = 'module_purchase' AND item_type = 'session_document' AND session_material_id IS NOT NULL AND training_session_id IS NULL AND module_id IS NULL)
            )");
        }

        Schema::create('session_document_entitlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('training_session_id')->constrained('training_sessions')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('session_material_id')->constrained('session_materials')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('order_item_id')->unique()->constrained('order_items')->restrictOnDelete()->restrictOnUpdate();
            $table->string('status', 20)->default('active');
            $table->tinyInteger('is_active_entitlement')->virtualAs("IF(status = 'active', 1, NULL)")->nullable();
            $table->dateTime('granted_at')->useCurrent();
            $table->dateTime('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete()->restrictOnUpdate();
            $table->text('revocation_reason')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'session_material_id', 'is_active_entitlement'], 'uq_session_document_entitlements_active');
            $table->index(['training_session_id', 'status'], 'idx_session_document_entitlements_session');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE session_document_entitlements ADD CONSTRAINT chk_session_document_entitlements_status CHECK (status IN ('active', 'revoked'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('session_document_entitlements');

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE order_items DROP CONSTRAINT chk_order_items_type_alignment');
            DB::statement("ALTER TABLE order_items ADD CONSTRAINT chk_order_items_type_alignment CHECK (
                (order_type = 'session_booking' AND item_type = 'training_session' AND training_session_id IS NOT NULL AND module_id IS NULL)
                OR (order_type = 'module_purchase' AND item_type = 'module' AND module_id IS NOT NULL AND training_session_id IS NULL)
            )");
        }

        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropForeign(['session_material_id']);
            $table->dropIndex('idx_order_items_session_material');
            $table->dropColumn('session_material_id');
        });
    }
};
