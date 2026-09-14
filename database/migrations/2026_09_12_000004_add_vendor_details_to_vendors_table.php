<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->text('residential_address')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('market_section')->nullable();
            $table->decimal('monthly_rent', 10, 2)->nullable();
            $table->string('billing_cycle')->default('Monthly');
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'contact_number', 'email', 'residential_address', 'photo_path',
                'market_section', 'monthly_rent', 'billing_cycle',
                'contract_start_date', 'contract_end_date',
            ]);
        });
    }
};
