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
            ['name' => 'To Do',       'color' => '#94a3b8', 'position' => 1, 'is_default' => true,  'is_done' => false],
            ['name' => 'In Progress', 'color' => '#6366f1', 'position' => 2, 'is_default' => false, 'is_done' => false],
            ['name' => 'In Review',   'color' => '#f59e0b', 'position' => 3, 'is_default' => false, 'is_done' => false],
            ['name' => 'Done',        'color' => '#22c55e', 'position' => 4, 'is_default' => false, 'is_done' => true],
        ];
        foreach ($columns as $col) {
            Column::create(array_merge($col, [
                'project_id' => $project->id,
                'slug'       => Str::slug($col['name']),
            ]));
        }
    }

    private function createLabels(Project $project): void
    {
        foreach ([
            ['name' => 'Bug',     'color' => '#ef4444'],
            ['name' => 'Feature', 'color' => '#6366f1'],
            ['name' => 'Chore',   'color' => '#94a3b8'],
            ['name' => 'Docs',    'color' => '#f59e0b'],
        ] as $label) {
            Label::create(array_merge($label, ['project_id' => $project->id]));
        }
    }

    public function run(): void
    {
        $admin   = User::where('email', 'admin@xynosoft.in')->firstOrFail();
        $manager = User::where('email', 'aljinas@xynosoft.in')->firstOrFail();
        $devTeam = Team::where('slug', 'development')->first();
        $qaTeam  = Team::where('slug', 'design-qa')->first();

        $members = User::whereIn('email', [
            'sunitha@xynosoft.in', 'pranathi@xynosoft.in', 'sajin@xynosoft.in',
            'lidiya@xynosoft.in',  'jinu@xynosoft.in',     'suhana@xynosoft.in',
            'annmaria@xynosoft.in',
        ])->get();

        $projectsData = [
            [
                'name'       => 'Workzen Platform',
                'status'     => 'active',
                'priority'   => 'high',
                'color'      => '#6366f1',
                'owner'      => $manager,
                'team'       => $devTeam,
                'due_date'   => now()->addMonths(3),
                'start_date' => now()->subWeeks(4),
            ],
            [
                'name'       => 'Mobile App v1',
                'status'     => 'active',
                'priority'   => 'critical',
                'color'      => '#ec4899',
                'owner'      => $manager,
                'team'       => $devTeam,
                'due_date'   => now()->addMonths(2),
                'start_date' => now()->subWeeks(6),
            ],
            [
                'name'       => 'Design System',
                'status'     => 'active',
                'priority'   => 'medium',
                'color'      => '#8b5cf6',
                'owner'      => $admin,
                'team'       => $qaTeam,
                'due_date'   => now()->addMonths(4),
                'start_date' => now()->subWeeks(2),
            ],
            [
                'name'       => 'API Gateway Migration',
                'status'     => 'planning',
                'priority'   => 'high',
                'color'      => '#14b8a6',
                'owner'      => $manager,
                'team'       => $devTeam,
                'due_date'   => now()->addMonths(5),
                'start_date' => now()->addWeeks(2),
            ],
            [
                'name'       => 'Company Website Redesign',
                'status'     => 'completed',
                'priority'   => 'medium',
                'color'      => '#f59e0b',
                'owner'      => $admin,
                'team'       => $qaTeam,
                'due_date'   => now()->subWeeks(2),
                'start_date' => now()->subMonths(3),
            ],
        ];

        foreach ($projectsData as $data) {
            $project = Project::firstOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name'       => $data['name'],
                    'team_id'    => $data['team']?->id,
                    'owner_id'   => $data['owner']->id,
                    'status'     => $data['status'],
                    'priority'   => $data['priority'],
                    'color'      => $data['color'],
                    'due_date'   => $data['due_date'],
                    'start_date' => $data['start_date'],
                    'is_public'  => false,
                ]
            );

            $project->members()->syncWithoutDetaching([$data['owner']->id => ['role' => 'manager']]);

            foreach ($members->random(min(4, $members->count())) as $m) {
                $project->members()->syncWithoutDetaching([$m->id => ['role' => 'developer']]);
            }

            $this->createColumns($project);
            $this->createLabels($project);
        }
    }
}
