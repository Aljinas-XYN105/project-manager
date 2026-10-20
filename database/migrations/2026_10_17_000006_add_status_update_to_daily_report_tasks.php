<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_report_tasks', function (Blueprint $table) {
            $table->string('status_update')->nullable()->after('task_id')
                  ->comment('Status reported for this task: todo/in_progress/in_review/done/blocked');
            $table->boolean('is_blocked')->default(false)->after('status_update');
        });
    }

    public function down(): void
    {
        Schema::table('daily_report_tasks', function (Blueprint $table) {
            $table->dropColumn(['status_update', 'is_blocked']);
        });
    }
};
