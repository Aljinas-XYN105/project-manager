<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('column_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->string('title');
            $table->longText('description')->nullable();
            $table->enum('status', ['todo', 'in_progress', 'in_review', 'done', 'cancelled'])->default('todo');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->unsignedInteger('position')->default(0);
            $table->tinyInteger('story_points')->unsigned()->nullable();
            $table->decimal('estimated_hours', 6, 2)->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('project_id');
            $table->index('column_id');
            $table->index('parent_id');
            $table->index('created_by');
            $table->index('status');
            $table->index('priority');
            $table->index('due_date');
            $table->index(['project_id', 'column_id', 'position']);
            $table->index('is_archived');

            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('column_id')->references('id')->on('columns');
            $table->foreign('parent_id')->references('id')->on('tasks')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
