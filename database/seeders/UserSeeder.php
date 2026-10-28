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
            ['email' => 'admin@xynosoft.in'],
            [
                'name'               => 'Admin',
                'password'           => Hash::make('Golden_123'),
                'job_title'          => 'System Administrator',
                'department'         => 'Management',
                'timezone'           => 'Asia/Kolkata',
                'is_active'          => true,
                'email_verified_at'  => now(),
            ]
        );
        $admin->assignRole('admin');

        // Manager
        $manager = User::firstOrCreate(
            ['email' => 'aljinas@xynosoft.in'],
            [
                'name'               => 'Aljinas',
                'password'           => Hash::make('Golden_123'),
                'job_title'          => 'Project Manager',
                'department'         => 'Engineering',
                'timezone'           => 'Asia/Kolkata',
                'is_active'          => true,
                'email_verified_at'  => now(),
                'manager_id'         => null,
            ]
        );
        $manager->assignRole('manager');

        // Members
        $members = [
            ['name' => 'Sunitha',   'email' => 'sunitha@xynosoft.in',   'job_title' => 'Full-Stack Developer',   'department' => 'Engineering'],
            ['name' => 'Pranathi',  'email' => 'pranathi@xynosoft.in',  'job_title' => 'Full-Stack Developer',    'department' => 'Engineering'],
            ['name' => 'Sajin',     'email' => 'sajin@xynosoft.in',     'job_title' => 'Mobile Developer', 'department' => 'Engineering'],
            ['name' => 'Lidiya',    'email' => 'lidiya@xynosoft.in',    'job_title' => 'Backend Developer',       'department' => 'Engineering'],
            ['name' => 'Jinu',      'email' => 'jinu@xynosoft.in',      'job_title' => 'QA Engineer',     'department' => 'Quality'],
            ['name' => 'Suhana',    'email' => 'suhana@xynosoft.in',    'job_title' => 'Frontend Developer',          'department' => 'Engineering'],
            ['name' => 'Annmaria',  'email' => 'annmaria@xynosoft.in',  'job_title' => 'Frontend Developer',      'department' => 'Engineering'],
            ['name' => 'Aishwarya', 'email' => 'aishwarya@xynosoft.in', 'job_title' => 'UI/UX Designer',       'department' => 'Design'],
        ];

        foreach ($members as $data) {
            $u = User::firstOrCreate(
                ['email' => $data['email']],
                array_merge($data, [
                    'password'          => Hash::make('112233'),
                    'timezone'          => 'Asia/Kolkata',
                    'is_active'         => true,
                    'email_verified_at' => now(),
                    'manager_id'        => $manager->id,
                ])
            );
            $u->assignRole('member');
        }
    }
}
