<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('standup_items', function (Blueprint $table) {
            $table->foreignId('task_id')->nullable()->after('standup_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('standup_items', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Task::class);
            $table->dropColumn('task_id');
        });
    }
};
