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
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->unique('uq_customer_profiles_user')
                ->constrained('users')
                ->cascadeOnDelete()
                ->restrictOnUpdate();
            $table->string('customer_type', 20)->default('individual');
            $table->string('phone', 30)->nullable();
            $table->string('job_title', 120)->nullable();
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
            $table->char('billing_country', 2)->default('MY');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('customer_type', 'idx_customer_profiles_type');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE customer_profiles ADD CONSTRAINT chk_customer_profiles_type CHECK (customer_type IN ('individual', 'company'))");
        }

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique('uq_categories_slug');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['is_active', 'sort_order'], 'idx_categories_active_sort');
        });

        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                ->constrained('categories')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('code', 50)->unique('uq_modules_code');
            $table->string('title', 200);
            $table->string('slug', 220)->unique('uq_modules_slug');
            $table->string('summary', 500)->nullable();
            $table->longText('description');
            $table->json('learning_objectives')->nullable();
            $table->string('cover_image_disk', 30)->nullable();
            $table->string('cover_image_path', 500)->nullable();
            $table->boolean('is_available_independently')->default(true);
            $table->decimal('independent_price', 12, 2)->nullable();
            $table->char('currency', 3)->default('MYR');
            $table->string('status', 20)->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->dateTime('archived_at')->nullable();
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
            $table->dateTime('deleted_at')->nullable();

            $table->index(['status', 'category_id', 'deleted_at'], 'idx_modules_catalogue');
            $table->index(['is_available_independently', 'status'], 'idx_modules_independent');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE modules ADD CONSTRAINT chk_modules_status CHECK (status IN ('draft', 'published', 'unpublished', 'archived'))");
            DB::statement("ALTER TABLE modules ADD CONSTRAINT chk_modules_price_non_negative CHECK (independent_price IS NULL OR independent_price >= 0)");
            DB::statement("ALTER TABLE modules ADD CONSTRAINT chk_modules_independent_price CHECK (is_available_independently = FALSE OR independent_price IS NOT NULL)");
        }

        Schema::create('module_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')
                ->constrained('modules')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('material_type', 30)->default('pdf');
            $table->string('storage_disk', 30)->default('r2');
            $table->string('storage_bucket', 100)->nullable();
            $table->string('storage_key', 500);
            $table->string('original_filename', 255);
            $table->string('mime_type', 127);
            $table->unsignedBigInteger('file_size_bytes');
            $table->char('checksum_sha256', 64)->nullable();
            $table->boolean('is_downloadable')->default(true);
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

            $table->index(['module_id', 'status', 'sort_order', 'deleted_at'], 'idx_module_materials_module');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE module_materials ADD CONSTRAINT chk_module_materials_type CHECK (material_type IN ('pdf', 'presentation', 'document', 'image', 'worksheet', 'other'))");
            DB::statement("ALTER TABLE module_materials ADD CONSTRAINT chk_module_materials_size CHECK (file_size_bytes > 0)");
            DB::statement("ALTER TABLE module_materials ADD CONSTRAINT chk_module_materials_status CHECK (status IN ('draft', 'published', 'archived'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_materials');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('customer_profiles');
    }
};
