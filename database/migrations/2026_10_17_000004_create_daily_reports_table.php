<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('report_date');
            $table->text('summary');
            $table->text('plan_for_tomorrow')->nullable();
            $table->text('blockers')->nullable();
            $table->decimal('total_hours', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'report_date']);
            $table->index('user_id');
            $table->index('report_date');

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
