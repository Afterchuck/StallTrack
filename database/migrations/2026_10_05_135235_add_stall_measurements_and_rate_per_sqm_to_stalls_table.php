<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stalls', function (Blueprint $table) {
            $table->decimal('length_m', 8, 2)->nullable();
            $table->decimal('width_m', 8, 2)->nullable();
            $table->decimal('rate_per_sqm', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stalls', function (Blueprint $table) {
            $table->dropColumn(['length_m', 'width_m', 'rate_per_sqm']);
        });
    }
};
