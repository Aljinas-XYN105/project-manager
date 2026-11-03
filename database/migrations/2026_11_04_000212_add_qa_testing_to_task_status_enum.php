<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE tasks MODIFY COLUMN status ENUM('todo','in_progress','qa_testing','in_review','done','cancelled') NOT NULL DEFAULT 'todo'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE tasks MODIFY COLUMN status ENUM('todo','in_progress','in_review','done','cancelled') NOT NULL DEFAULT 'todo'");
    }
};
