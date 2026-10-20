<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permissions = ['leave.approve', 'leave.manage', 'announcements.manage', 'employees.view-private'];

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_code', 30)->nullable()->unique()->after('job_title');
            $table->string('department', 80)->nullable()->after('employee_code');
            $table->string('employment_type', 20)->default('full_time')->after('department');
            $table->foreignId('manager_id')->nullable()->after('employment_type')->constrained('users')->nullOnDelete();
            $table->date('joined_on')->nullable()->after('manager_id');
            $table->date('date_of_birth')->nullable()->after('joined_on');
            $table->string('phone', 40)->nullable()->after('date_of_birth');
            $table->string('emergency_contact_name', 120)->nullable()->after('phone');
            $table->string('emergency_contact_phone', 40)->nullable()->after('emergency_contact_name');
        });

        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->string('color', 20)->default('violet');
            $table->decimal('days_per_year', 5, 1)->default(0); // 0 = unlimited
            $table->boolean('is_paid')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_half_day')->default(false);
            $table->decimal('days', 5, 1);
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 500)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->date('date')->unique();
            $table->timestamps();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->text('body');
            $table->string('category', 30)->default('general');
            $table->boolean('is_pinned')->default(false);
            $table->date('expires_on')->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('leave_types')->insert([
            ['name' => 'Annual Leave', 'color' => 'violet',  'days_per_year' => 20, 'is_paid' => true,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Sick Leave',   'color' => 'rose',    'days_per_year' => 10, 'is_paid' => true,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Casual Leave', 'color' => 'amber',   'days_per_year' => 6,  'is_paid' => true,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Unpaid Leave', 'color' => 'slate',   'days_per_year' => 0,  'is_paid' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach ($this->permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        Role::where('name', 'admin')->first()?->givePermissionTo($this->permissions);
        Role::where('name', 'manager')->first()?->givePermissionTo(['leave.approve']);
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_types');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manager_id');
            $table->dropUnique(['employee_code']);
            $table->dropColumn(['employee_code', 'department', 'employment_type', 'joined_on', 'date_of_birth',
                'phone', 'emergency_contact_name', 'emergency_contact_phone']);
        });
        Permission::whereIn('name', $this->permissions)->delete();
    }
};
