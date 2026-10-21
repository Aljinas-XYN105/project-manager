<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->string('status', 20)->default('stopped')->after('description'); // running, paused, stopped
            $table->timestamp('paused_at')->nullable()->after('stopped_at');
            $table->unsignedInteger('paused_seconds')->default(0)->after('duration_seconds');
            $table->boolean('auto_paused')->default(false)->after('paused_seconds'); // paused by logout
        });
    }

    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropColumn(['status', 'paused_at', 'paused_seconds', 'auto_paused']);
        });
    }
};
