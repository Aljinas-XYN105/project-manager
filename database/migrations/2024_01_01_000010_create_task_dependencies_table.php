<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('depends_on_id');
            $table->enum('type', ['blocks', 'is_blocked_by', 'duplicates', 'relates_to'])->default('blocks');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['task_id', 'depends_on_id']);

            $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
            $table->foreign('depends_on_id')->references('id')->on('tasks')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_dependencies');
    }
};
