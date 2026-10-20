<?php

namespace Database\Seeders;

use App\Models\Column;
use App\Models\Label;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProjectSeeder extends Seeder
{
    private function createColumns(Project $project): void
    {
        $columns = [
            ['name' => 'To Do',      'color' => '#94a3b8', 'position' => 1, 'is_default' => true, 'is_done' => false],
            ['name' => 'In Progress','color' => '#6366f1', 'position' => 2, 'is_default' => false,'is_done' => false],
            ['name' => 'In Review',  'color' => '#f59e0b', 'position' => 3, 'is_default' => false,'is_done' => false],
            ['name' => 'Done',       'color' => '#22c55e', 'position' => 4, 'is_default' => false,'is_done' => true],
        ];
        foreach ($columns as $col) {
            Column::create(array_merge($col, ['project_id' => $project->id, 'slug' => Str::slug($col['name'])]));
        }
    }

    private function createLabels(Project $project): void
    {
        $labels = [
            ['name' => 'Bug',     'color' => '#ef4444'],
            ['name' => 'Feature', 'color' => '#6366f1'],
            ['name' => 'Chore',   'color' => '#94a3b8'],
            ['name' => 'Docs',    'color' => '#f59e0b'],
        ];
        foreach ($labels as $label) {
            Label::create(array_merge($label, ['project_id' => $project->id]));
        }
    }

    public function run(): void
    {
        $admin  = User::where('email', 'admin@example.com')->first();
        $maria  = User::where('email', 'maria@example.com')->first();
        $james  = User::where('email', 'james@example.com')->first();
        $priya  = User::where('email', 'priya@example.com')->first();
        $engTeam    = Team::where('slug', 'engineering')->first();
        $designTeam = Team::where('slug', 'design')->first();

        $projectsData = [
            [
                'name'     => 'Platform Redesign',
                'status'   => 'active',
                'priority' => 'high',
                'color'    => '#6366f1',
                'owner'    => $maria,
                'team'     => $engTeam,
                'due_date' => now()->addMonths(3),
                'start_date' => now()->subWeeks(4),
            ],
            [
                'name'     => 'Mobile App v2',
                'status'   => 'active',
                'priority' => 'critical',
                'color'    => '#ec4899',
                'owner'    => $admin,
                'team'     => $engTeam,
                'due_date' => now()->addMonths(2),
                'start_date' => now()->subWeeks(6),
            ],
            [
                'name'     => 'Design System',
                'status'   => 'active',
                'priority' => 'medium',
                'color'    => '#8b5cf6',
                'owner'    => $james,
                'team'     => $designTeam,
                'due_date' => now()->addMonths(4),
                'start_date' => now()->subWeeks(2),
            ],
            [
                'name'     => 'API Gateway Migration',
                'status'   => 'planning',
                'priority' => 'high',
                'color'    => '#14b8a6',
                'owner'    => $maria,
                'team'     => $engTeam,
                'due_date' => now()->addMonths(5),
                'start_date' => now()->addWeeks(2),
            ],
            [
                'name'     => 'Q1 Marketing Site',
                'status'   => 'completed',
                'priority' => 'medium',
                'color'    => '#f59e0b',
                'owner'    => $james,
                'team'     => $designTeam,
                'due_date' => now()->subWeeks(2),
                'start_date' => now()->subMonths(3),
            ],
            [
                'name'     => 'Internal Tools',
                'status'   => 'on_hold',
                'priority' => 'low',
                'color'    => '#64748b',
                'owner'    => $priya,
                'team'     => $engTeam,
                'due_date' => now()->addMonths(6),
                'start_date' => now()->subWeeks(1),
            ],
        ];

        $allMembers = User::whereIn('email', [
            'sam@example.com','lily@example.com','carlos@example.com',
            'aisha@example.com','tom@example.com','nadia@example.com',
            'omar@example.com','zoe@example.com',
        ])->get();

        foreach ($projectsData as $data) {
            $project = Project::firstOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name'       => $data['name'],
                    'team_id'    => $data['team']->id,
                    'owner_id'   => $data['owner']->id,
                    'status'     => $data['status'],
                    'priority'   => $data['priority'],
                    'color'      => $data['color'],
                    'due_date'   => $data['due_date'],
                    'start_date' => $data['start_date'],
                    'is_public'  => false,
                ]
            );

            // Add owner as manager
            $project->members()->syncWithoutDetaching([$data['owner']->id => ['role' => 'manager']]);
            // Add random members
            $members = $allMembers->random(min(4, $allMembers->count()));
            foreach ($members as $m) {
                $project->members()->syncWithoutDetaching([$m->id => ['role' => 'developer']]);
            }

            $this->createColumns($project);
            $this->createLabels($project);
        }
    }
}
