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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique('uq_roles_slug');
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->boolean('is_system')->default(true);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE roles ADD CONSTRAINT chk_roles_slug CHECK (slug IN ('administrator', 'instructor', 'customer'))");
        }

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique('uq_permissions_slug');
            $table->string('name', 150);
            $table->string('module_group', 50)->index('idx_permissions_group');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('role_id')
                ->constrained('roles')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('assigned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->restrictOnUpdate();
            $table->dateTime('assigned_at')->useCurrent();

            $table->primary(['user_id', 'role_id']);
            $table->index('role_id', 'idx_role_user_role');
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')
                ->constrained('permissions')
                ->cascadeOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('role_id')
                ->constrained('roles')
                ->cascadeOnDelete()
                ->restrictOnUpdate();

            $table->primary(['permission_id', 'role_id']);
            $table->index('role_id', 'idx_permission_role_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
