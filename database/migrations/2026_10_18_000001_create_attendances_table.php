<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->timestamp('clock_in_at');
            $table->timestamp('clock_out_at')->nullable();
            $table->string('clock_in_ip', 45)->nullable();
            $table->string('clock_out_ip', 45)->nullable();
            $table->string('source', 20)->default('login');
            $table->string('note', 500)->nullable();
            $table->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'work_date']);
            $table->index('work_date');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('availability', 20)->default('offline')->after('is_active');
            $table->string('availability_note', 120)->nullable()->after('availability');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['availability', 'availability_note']);
        });
    }
};
