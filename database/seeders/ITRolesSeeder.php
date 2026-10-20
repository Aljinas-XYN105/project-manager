<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ITRolesSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Standard member-level permissions (view + work, no admin actions)
        $memberPerms = [
            'projects.viewAny', 'projects.view',
            'tasks.viewAny', 'tasks.view', 'tasks.create', 'tasks.update', 'tasks.move',
            'teams.viewAny', 'teams.view',
            'time-entries.view', 'time-entries.create', 'time-entries.update',
            'users.viewAny', 'users.view',
            'reports.view',
            'comments.create', 'comments.update',
        ];

        // Lead-level permissions (can assign tasks, pin comments)
        $leadPerms = array_merge($memberPerms, [
            'tasks.assign', 'tasks.delete',
            'comments.delete', 'comments.pin',
            'reports.export',
        ]);

        $roles = [
            // Core IT roles
            ['name' => 'developer',        'perms' => $memberPerms],
            ['name' => 'designer',         'perms' => $memberPerms],
            ['name' => 'qa_engineer',      'perms' => $memberPerms],
            ['name' => 'devops_engineer',  'perms' => $memberPerms],
            ['name' => 'business_analyst', 'perms' => $memberPerms],
            ['name' => 'product_owner',    'perms' => $leadPerms],
            ['name' => 'scrum_master',     'perms' => $leadPerms],
            ['name' => 'tech_lead',        'perms' => $leadPerms],
        ];

        foreach ($roles as $data) {
            $role = Role::firstOrCreate(['name' => $data['name']]);
            $role->syncPermissions($data['perms']);
        }
    }
}
