<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL ENUM requires a full redeclaration to add values
        DB::statement("ALTER TABLE standup_items MODIFY COLUMN status ENUM('todo','in_progress','in_review','qa_testing','done') NOT NULL DEFAULT 'todo'");
    }

    public function down(): void
    {
        // Remap qa_testing → in_review before dropping the value
        DB::statement("UPDATE standup_items SET status = 'in_review' WHERE status = 'qa_testing'");
        DB::statement("ALTER TABLE standup_items MODIFY COLUMN status ENUM('todo','in_progress','in_review','done') NOT NULL DEFAULT 'todo'");
    }
};
