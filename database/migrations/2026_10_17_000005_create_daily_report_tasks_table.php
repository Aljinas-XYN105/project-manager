<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_report_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('daily_report_id');
            $table->unsignedBigInteger('task_id')->nullable();
            $table->string('task_description');
            $table->decimal('hours_spent', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('daily_report_id');
            $table->index('task_id');

            $table->foreign('daily_report_id')->references('id')->on('daily_reports')->cascadeOnDelete();
            $table->foreign('task_id')->references('id')->on('tasks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_report_tasks');
    }
};
