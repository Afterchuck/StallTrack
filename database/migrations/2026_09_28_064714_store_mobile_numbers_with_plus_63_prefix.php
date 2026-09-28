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
        DB::unprepared('DROP TRIGGER IF EXISTS users_mobile_number_must_be_11_digits_on_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS users_mobile_number_must_be_11_digits_on_update');

        foreach (DB::table('users')->whereNotNull('mobile_number')->get(['id', 'mobile_number']) as $user) {
            $mobileNumber = (string) $user->mobile_number;
            $digits = preg_replace('/\D+/', '', $mobileNumber);

            if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
                $normalizedMobileNumber = '+63'.substr($digits, 1);
            } elseif (strlen($digits) === 12 && str_starts_with($digits, '63')) {
                $normalizedMobileNumber = '+'.$digits;
            } elseif (strlen($digits) === 10 && str_starts_with($digits, '9')) {
                $normalizedMobileNumber = '+63'.$digits;
            } else {
                continue;
            }

            if ($normalizedMobileNumber !== $mobileNumber) {
                DB::table('users')->where('id', $user->id)->update(['mobile_number' => $normalizedMobileNumber]);
            }
        }

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER users_mobile_number_must_use_plus_63_on_insert
            BEFORE INSERT ON users
            WHEN NEW.mobile_number IS NOT NULL
                AND (length(NEW.mobile_number) != 13 OR NEW.mobile_number NOT GLOB '+63[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]')
            BEGIN
                SELECT RAISE(ABORT, 'Mobile number must use +63 followed by 10 digits.');
            END;
            SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER users_mobile_number_must_use_plus_63_on_update
            BEFORE UPDATE OF mobile_number ON users
            WHEN NEW.mobile_number IS NOT NULL
                AND (length(NEW.mobile_number) != 13 OR NEW.mobile_number NOT GLOB '+63[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]')
            BEGIN
                SELECT RAISE(ABORT, 'Mobile number must use +63 followed by 10 digits.');
            END;
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS users_mobile_number_must_use_plus_63_on_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS users_mobile_number_must_use_plus_63_on_update');

        foreach (DB::table('users')->whereNotNull('mobile_number')->get(['id', 'mobile_number']) as $user) {
            $mobileNumber = (string) $user->mobile_number;

            if (strlen($mobileNumber) === 13 && str_starts_with($mobileNumber, '+63') && ctype_digit(substr($mobileNumber, 3))) {
                DB::table('users')->where('id', $user->id)->update([
                    'mobile_number' => '0'.substr($mobileNumber, 3),
                ]);
            }
        }

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
};
