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
        Schema::table('bills', function (Blueprint $table) {
            $table->foreignId('rental_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('contract_number')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('last_reminded_at')->nullable();
            $table->index(['vendor_id', 'period_start']);
            $table->unique(['rental_id', 'period_start', 'period_end']);
        });
        Schema::table('bills', function (Blueprint $table) {
            $table->dropUnique(['vendor_id', 'period_start', 'period_end']);
        });
        Schema::table('rentals', function (Blueprint $table) {
            $table->unsignedSmallInteger('billing_due_days')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new RuntimeException('This migration preserves multi-stall billing history. Use a forward migration; restoring vendor-only uniqueness may destroy valid bills.');
    }
};
