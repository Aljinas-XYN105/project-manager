<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permissions = [
        'activity.view', 'attendance.view', 'attendance.manage', 'roles.manage', 'users.manage',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        Role::where('name', 'admin')->first()?->givePermissionTo($this->permissions);
        Role::where('name', 'manager')->first()?->givePermissionTo(['attendance.view']);
    }

    public function down(): void
    {
        Permission::whereIn('name', ['activity.view', 'attendance.view', 'attendance.manage', 'roles.manage'])->delete();
    }
};
