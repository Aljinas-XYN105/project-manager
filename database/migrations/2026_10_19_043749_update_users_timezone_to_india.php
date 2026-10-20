<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Set all users to India Standard Time.
        DB::table('users')->update(['timezone' => 'Asia/Kolkata']);

        // Also update app default timezone.
        // (APP_TIMEZONE env var should also be set to Asia/Kolkata in production.)
    }

    public function down(): void
    {
        DB::table('users')->update(['timezone' => 'UTC']);
    }
};
