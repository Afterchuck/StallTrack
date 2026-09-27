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
        if (Schema::hasColumn('payments', 'status')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->string('status')->default('Recorded');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /**
         * The status column may predate this migration in existing installations.
         */
    }
};
