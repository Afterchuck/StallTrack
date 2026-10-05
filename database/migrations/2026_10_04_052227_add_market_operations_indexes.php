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
        Schema::table('rentals', function (Blueprint $table): void {
            $table->index(['stall_id', 'status']);
            $table->index(['vendor_id', 'status']);
            $table->index(['status', 'end_date']);
        });

        Schema::table('stalls', function (Blueprint $table): void {
            $table->index(['status', 'market_section']);
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->index(['vendor_id', 'paid_at']);
            $table->index(['status', 'paid_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table): void {
            $table->dropIndex(['stall_id', 'status']);
            $table->dropIndex(['vendor_id', 'status']);
            $table->dropIndex(['status', 'end_date']);
        });

        Schema::table('stalls', function (Blueprint $table): void {
            $table->dropIndex(['status', 'market_section']);
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['vendor_id', 'paid_at']);
            $table->dropIndex(['status', 'paid_at']);
        });
    }
};
