<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE payments DROP CONSTRAINT chk_payments_gateway");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT chk_payments_gateway CHECK (gateway IN ('billplz', 'paystack'))");
        DB::statement("ALTER TABLE payments ALTER COLUMN gateway SET DEFAULT 'paystack'");
        DB::statement("ALTER TABLE payment_webhook_events ALTER COLUMN gateway SET DEFAULT 'paystack'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE payments DROP CONSTRAINT chk_payments_gateway");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT chk_payments_gateway CHECK (gateway IN ('billplz'))");
        DB::statement("ALTER TABLE payments ALTER COLUMN gateway SET DEFAULT 'billplz'");
        DB::statement("ALTER TABLE payment_webhook_events ALTER COLUMN gateway SET DEFAULT 'billplz'");
    }
};
