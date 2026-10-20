<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->enum('type', ['task', 'bug', 'feature', 'story', 'epic'])->default('task')->after('created_by');
            $table->unsignedInteger('ticket_number')->nullable()->after('type');
            $table->unsignedBigInteger('sprint_id')->nullable()->after('is_archived');
            $table->enum('severity', ['blocker', 'critical', 'major', 'minor', 'trivial'])->nullable()->after('sprint_id');
            $table->text('steps_to_reproduce')->nullable()->after('severity');
            $table->text('expected_behavior')->nullable()->after('steps_to_reproduce');
            $table->text('actual_behavior')->nullable()->after('expected_behavior');
            $table->string('environment')->nullable()->after('actual_behavior');

            $table->index(['project_id', 'ticket_number']);
            $table->index('type');
            $table->index('sprint_id');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['type', 'ticket_number', 'sprint_id', 'severity', 'steps_to_reproduce', 'expected_behavior', 'actual_behavior', 'environment']);
        });
    }
};
