<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('columns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('name');
            $table->string('slug');
            $table->string('color', 7)->default('#6B7280');
            $table->smallInteger('position')->unsigned()->default(0);
            $table->smallInteger('wip_limit')->unsigned()->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_done')->default(false);
            $table->timestamps();

            $table->unique(['project_id', 'slug']);
            $table->index(['project_id', 'position']);

            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('columns');
    }
};
