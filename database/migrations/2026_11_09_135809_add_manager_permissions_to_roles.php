<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $new = [
            'standups.view-team',    // view all team standups, member-day view
            'standups.manage',       // create on behalf of others, export
            'daily-reports.view-team', // see all team reports, edit any/past report
            'tasks.view-all',        // see all tasks in search, my-tasks, chat mentions
            'messages.delete-any',   // delete anyone's chat message
        ];

        foreach ($new as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        // Give admin all permissions
        $admin = Role::findByName('admin');
        $admin->syncPermissions(Permission::all());

        // Give manager the new permissions (on top of existing ones)
        $manager = Role::findByName('manager');
        $manager->givePermissionTo($new);

        // expenses.review was already in seeder but make sure manager has it
        $manager->givePermissionTo('expenses.review');
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $remove = [
            'standups.view-team',
            'standups.manage',
            'daily-reports.view-team',
            'tasks.view-all',
            'messages.delete-any',
        ];

        foreach ($remove as $name) {
            $perm = Permission::findByName($name);
            if ($perm) $perm->delete();
        }
    }
};
