<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Projects
            'projects.viewAny', 'projects.view', 'projects.create', 'projects.update', 'projects.delete', 'projects.archive',
            // Tasks
            'tasks.viewAny', 'tasks.view', 'tasks.create', 'tasks.update', 'tasks.delete', 'tasks.move', 'tasks.assign',
            // Teams
            'teams.viewAny', 'teams.view', 'teams.create', 'teams.update', 'teams.delete',
            // Time entries
            'time-entries.view', 'time-entries.create', 'time-entries.update', 'time-entries.delete',
            // Users
            'users.viewAny', 'users.view', 'users.manage',
            // Reports
            'reports.view', 'reports.export',
            // Comments
            'comments.create', 'comments.update', 'comments.delete', 'comments.pin',
            // Administration
            'activity.view', 'attendance.view', 'attendance.manage', 'roles.manage',
            // HR
            'leave.approve', 'leave.manage', 'announcements.manage', 'employees.view-private',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Admin — all permissions
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        // Manager
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->syncPermissions([
            'projects.viewAny', 'projects.view', 'projects.create', 'projects.update', 'projects.archive',
            'tasks.viewAny', 'tasks.view', 'tasks.create', 'tasks.update', 'tasks.delete', 'tasks.move', 'tasks.assign',
            'teams.viewAny', 'teams.view', 'teams.create', 'teams.update',
            'time-entries.view', 'time-entries.create', 'time-entries.update',
            'users.viewAny', 'users.view',
            'reports.view', 'reports.export',
            'comments.create', 'comments.update', 'comments.delete', 'comments.pin',
            'attendance.view', 'leave.approve',
        ]);

        // Member
        $member = Role::firstOrCreate(['name' => 'member']);
        $member->syncPermissions([
            'projects.viewAny', 'projects.view',
            'tasks.viewAny', 'tasks.view', 'tasks.create', 'tasks.update', 'tasks.move',
            'teams.viewAny', 'teams.view',
            'time-entries.view', 'time-entries.create', 'time-entries.update',
            'users.viewAny', 'users.view',
            'reports.view',
            'comments.create', 'comments.update',
        ]);
    }
}
