<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'       => 'Alex Admin',
                'password'   => Hash::make('password'),
                'job_title'  => 'Product Manager',
                'timezone'   => 'America/New_York',
                'is_active'  => true,
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        // Managers
        $managers = [
            ['name' => 'Maria Chen',   'email' => 'maria@example.com',   'job_title' => 'Engineering Manager'],
            ['name' => 'James Rivera', 'email' => 'james@example.com',   'job_title' => 'Design Lead'],
            ['name' => 'Priya Nair',   'email' => 'priya@example.com',   'job_title' => 'QA Manager'],
        ];

        foreach ($managers as $data) {
            $u = User::firstOrCreate(
                ['email' => $data['email']],
                array_merge($data, [
                    'password' => Hash::make('password'),
                    'timezone' => 'America/Chicago',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ])
            );
            $u->assignRole('manager');
        }

        // Members
        $members = [
            ['name' => 'Sam Torres',    'email' => 'sam@example.com',    'job_title' => 'Frontend Developer'],
            ['name' => 'Lily Zhang',    'email' => 'lily@example.com',   'job_title' => 'Backend Developer'],
            ['name' => 'Carlos Gomez',  'email' => 'carlos@example.com', 'job_title' => 'Full-Stack Developer'],
            ['name' => 'Aisha Patel',   'email' => 'aisha@example.com',  'job_title' => 'UI Designer'],
            ['name' => 'Tom Wilson',    'email' => 'tom@example.com',    'job_title' => 'DevOps Engineer'],
            ['name' => 'Nadia Kowalski','email' => 'nadia@example.com',  'job_title' => 'Frontend Developer'],
            ['name' => 'Omar Hassan',   'email' => 'omar@example.com',   'job_title' => 'Mobile Developer'],
            ['name' => 'Zoe Bennett',   'email' => 'zoe@example.com',    'job_title' => 'UX Researcher'],
        ];

        foreach ($members as $data) {
            $u = User::firstOrCreate(
                ['email' => $data['email']],
                array_merge($data, [
                    'password' => Hash::make('password'),
                    'timezone' => 'America/Los_Angeles',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ])
            );
            $u->assignRole('member');
        }
    }
}
