<?php

namespace Database\Seeders;

use App\Models\Column;
use App\Models\Label;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $manager = User::where('email', 'aljinas@xynosoft.in')->first();
        $admin   = User::where('email', 'admin@xynosoft.in')->first();

        if (! $manager) {
            $this->command->warn('Manager user not found — run UserSeeder first.');
            return;
        }

        $allMembers = User::whereHas('roles', fn ($q) => $q->where('name', 'member'))
            ->pluck('id')
            ->toArray();

        $projects = [
            [
                'name'        => 'QR Menu',
                'description' => 'Digital QR-based menu system for restaurants and cafes. Customers scan a QR code to browse the menu and place orders.',
                'status'      => 'active',
                'priority'    => 'high',
                'color'       => '#f59e0b',
                'start_date'  => now()->subDays(30),
                'due_date'    => now()->addDays(60),
                'is_public'   => true,
                'members'     => ['sunitha@xynosoft.in', 'pranathi@xynosoft.in', 'sajin@xynosoft.in', 'suhana@xynosoft.in'],
                'labels'      => [
                    ['name' => 'Frontend',  'color' => '#6366f1'],
                    ['name' => 'Backend',   'color' => '#0ea5e9'],
                    ['name' => 'Mobile',    'color' => '#10b981'],
                    ['name' => 'Bug',       'color' => '#ef4444'],
                ],
            ],
            [
                'name'        => 'Raedna',
                'description' => 'Internal HRMS and workforce management platform for tracking attendance, leave, payroll and employee performance.',
                'status'      => 'active',
                'priority'    => 'high',
                'color'       => '#f59e0b',
                'start_date'  => now()->subDays(60),
                'due_date'    => now()->addDays(90),
                'is_public'   => true,
                'members'     => ['sunitha@xynosoft.in', 'lidiya@xynosoft.in', 'annmaria@xynosoft.in', 'jinu@xynosoft.in'],
                'labels'      => [
                    ['name' => 'HR',        'color' => '#8b5cf6'],
                    ['name' => 'Backend',   'color' => '#0ea5e9'],
                    ['name' => 'UI/UX',     'color' => '#ec4899'],
                    ['name' => 'Bug',       'color' => '#ef4444'],
                ],
            ],
            [
                'name'        => 'Saloon POS',
                'description' => 'Point-of-sale system tailored for salons and beauty parlours, including appointment booking, billing and inventory.',
                'status'      => 'active',
                'priority'    => 'medium',
                'color'       => '#ec4899',
                'start_date'  => now()->subDays(15),
                'due_date'    => now()->addDays(75),
                'is_public'   => true,
                'members'     => ['pranathi@xynosoft.in', 'suhana@xynosoft.in', 'aishwarya@xynosoft.in', 'jinu@xynosoft.in'],
                'labels'      => [
                    ['name' => 'Frontend',  'color' => '#6366f1'],
                    ['name' => 'Backend',   'color' => '#0ea5e9'],
                    ['name' => 'Design',    'color' => '#f59e0b'],
                    ['name' => 'Bug',       'color' => '#ef4444'],
                ],
            ],
            [
                'name'        => 'Laundry POS',
                'description' => 'POS and order management system for laundry businesses — track garments, generate bills and manage customer pickups.',
                'status'      => 'active',
                'priority'    => 'medium',
                'color'       => '#10b981',
                'start_date'  => now()->subDays(20),
                'due_date'    => now()->addDays(50),
                'is_public'   => true,
                'members'     => ['sajin@xynosoft.in', 'lidiya@xynosoft.in', 'annmaria@xynosoft.in', 'sunitha@xynosoft.in'],
                'labels'      => [
                    ['name' => 'Frontend',  'color' => '#6366f1'],
                    ['name' => 'Backend',   'color' => '#0ea5e9'],
                    ['name' => 'Mobile',    'color' => '#10b981'],
                    ['name' => 'Bug',       'color' => '#ef4444'],
                ],
            ],
            [
                'name'        => 'Restaurant POS',
                'description' => 'Full-featured restaurant POS with table management, KOT printing, billing, inventory and daily sales reporting.',
                'status'      => 'active',
                'priority'    => 'critical',
                'color'       => '#3b82f6',
                'start_date'  => now()->subDays(45),
                'due_date'    => now()->addDays(30),
                'is_public'   => true,
                'members'     => ['sunitha@xynosoft.in', 'pranathi@xynosoft.in', 'lidiya@xynosoft.in', 'sajin@xynosoft.in', 'jinu@xynosoft.in'],
                'labels'      => [
                    ['name' => 'Frontend',  'color' => '#6366f1'],
                    ['name' => 'Backend',   'color' => '#0ea5e9'],
                    ['name' => 'Mobile',    'color' => '#10b981'],
                    ['name' => 'Billing',   'color' => '#f59e0b'],
                    ['name' => 'Bug',       'color' => '#ef4444'],
                ],
            ],
        ];

        $defaultColumns = [
            ['name' => 'To Do',       'color' => '#94a3b8', 'position' => 1, 'is_default' => true,  'is_done' => false],
            ['name' => 'In Progress', 'color' => '#3b82f6', 'position' => 2, 'is_default' => false, 'is_done' => false],
            ['name' => 'In Review',   'color' => '#f59e0b', 'position' => 3, 'is_default' => false, 'is_done' => false],
            ['name' => 'Done',        'color' => '#10b981', 'position' => 4, 'is_default' => false, 'is_done' => true],
        ];

        foreach ($projects as $data) {
            $memberEmails = $data['members'];
            $labelDefs    = $data['labels'];
            unset($data['members'], $data['labels']);

            $project = Project::firstOrCreate(
                ['name' => $data['name'], 'owner_id' => $manager->id],
                array_merge($data, ['owner_id' => $manager->id])
            );

            // Columns
            if ($project->columns()->count() === 0) {
                foreach ($defaultColumns as $col) {
                    Column::create(array_merge($col, ['project_id' => $project->id]));
                }
            }

            // Labels
            foreach ($labelDefs as $lbl) {
                Label::firstOrCreate(
                    ['project_id' => $project->id, 'name' => $lbl['name']],
                    ['color' => $lbl['color']]
                );
            }

            // Members — always add manager as owner-member, plus listed members
            $memberIds = [];
            if ($admin) {
                $memberIds[$admin->id] = ['role' => 'manager'];
            }
            foreach ($memberEmails as $email) {
                $u = User::where('email', $email)->first();
                if ($u) {
                    $memberIds[$u->id] = ['role' => 'developer'];
                }
            }
            $project->members()->syncWithoutDetaching($memberIds);
        }

        $this->command->info('ProjectSeeder: ' . count($projects) . ' projects seeded.');
    }
}
