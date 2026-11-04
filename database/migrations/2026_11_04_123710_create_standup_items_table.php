<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standup_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('standup_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->enum('status', ['todo', 'in_progress', 'in_review', 'done'])->default('todo');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['standup_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standup_items');
    }
};
