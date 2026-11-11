<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('expected_hours_per_day', 4, 2)->default(8.00)->after('availability_note');
            $table->time('expected_start_time')->nullable()->after('expected_hours_per_day'); // e.g. 09:00:00
            $table->json('work_days')->nullable()->after('expected_start_time'); // ['mon','tue','wed','thu','fri']
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['expected_hours_per_day', 'expected_start_time', 'work_days']);
        });
    }
};
