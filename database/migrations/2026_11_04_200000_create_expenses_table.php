<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('category');
            $table->date('expense_date');
            $table->text('description')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('receipt_name')->nullable();
            $table->string('status')->default('pending'); // pending|approved|rejected
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
