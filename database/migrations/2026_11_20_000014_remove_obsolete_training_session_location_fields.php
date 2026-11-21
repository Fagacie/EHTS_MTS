<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_sessions', function (Blueprint $table): void {
            $table->dropColumn([
                'venue_postcode',
                'venue_map_url',
                'venue_notes',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('training_sessions', function (Blueprint $table): void {
            $table->string('venue_postcode', 20)->nullable();
            $table->string('venue_map_url', 500)->nullable();
            $table->text('venue_notes')->nullable();
        });
    }
};
