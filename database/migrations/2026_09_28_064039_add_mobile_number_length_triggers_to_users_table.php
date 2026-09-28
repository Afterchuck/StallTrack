<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER users_mobile_number_must_be_11_digits_on_insert
            BEFORE INSERT ON users
            WHEN NEW.mobile_number IS NOT NULL
                AND (length(NEW.mobile_number) != 11 OR NEW.mobile_number GLOB '*[^0-9]*')
            BEGIN
                SELECT RAISE(ABORT, 'Mobile number must contain exactly 11 digits.');
            END;
            SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER users_mobile_number_must_be_11_digits_on_update
            BEFORE UPDATE OF mobile_number ON users
            WHEN NEW.mobile_number IS NOT NULL
                AND (length(NEW.mobile_number) != 11 OR NEW.mobile_number GLOB '*[^0-9]*')
            BEGIN
                SELECT RAISE(ABORT, 'Mobile number must contain exactly 11 digits.');
            END;
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS users_mobile_number_must_be_11_digits_on_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS users_mobile_number_must_be_11_digits_on_update');
    }
};
