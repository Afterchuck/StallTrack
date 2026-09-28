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
        if (Schema::hasColumn('users', 'mobile_number')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile_number', 30)->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('users', 'mobile_number')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('mobile_number');
        });
    }
};
