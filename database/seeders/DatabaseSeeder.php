<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            TeamSeeder::class,
            ProjectSeeder::class,
            TaskSeeder::class,
            TimeEntrySeeder::class,
            CommentSeeder::class,
            HrDemoSeeder::class,
        ]);
    }
}
