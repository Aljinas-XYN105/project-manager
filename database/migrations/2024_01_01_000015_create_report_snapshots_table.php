<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->enum('type', ['time', 'project', 'team', 'custom']);
            $table->json('filters');
            $table->longText('result_data')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('type');

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_snapshots');
    }
};
