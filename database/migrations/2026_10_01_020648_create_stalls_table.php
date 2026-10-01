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
        Schema::create('stalls', function (Blueprint $table): void {
            $table->id();
            $table->string('stall_number', 20)->unique();
            $table->string('market_section', 100);
            $table->string('location')->nullable();
            $table->string('stall_type')->nullable();
            $table->string('dimensions')->nullable();
            $table->decimal('monthly_rate', 10, 2)->nullable();
            $table->string('status')->default('Available');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stalls');
    }
};
