<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->date('due_date')->nullable()->after('amount');
            $table->date('paid_at')->nullable()->change();
            $table->string('receipt_number')->nullable()->change();
        });

        DB::table('payments')->where('status', 'Recorded')->update(['status' => 'Paid']);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn('due_date');
            $table->date('paid_at')->nullable(false)->change();
            $table->string('receipt_number')->nullable(false)->change();
        });
    }
};
