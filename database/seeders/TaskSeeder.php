<?php

namespace Database\Seeders;

use App\Models\Column;
use App\Models\Label;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    private array $taskTitles = [
        'Set up CI/CD pipeline', 'Implement authentication', 'Design onboarding flow',
        'Fix login page bug', 'Write API documentation', 'Add dark mode support',
        'Optimise database queries', 'Implement search functionality', 'Create user dashboard',
        'Add export to CSV', 'Refactor service layer', 'Write unit tests for auth',
        'Implement notifications', 'Add pagination to lists', 'Design email templates',
        'Set up error tracking', 'Implement role-based access', 'Create admin panel',
        'Add file upload support', 'Build reporting module', 'Implement drag-and-drop',
        'Add keyboard shortcuts', 'Create mobile responsive layout', 'Implement real-time updates',
        'Write integration tests', 'Set up staging environment', 'Implement audit logging',
        'Add multi-language support', 'Create onboarding wizard', 'Performance profiling',
        'Fix critical security issue', 'Implement OAuth login', 'Database schema review',
        'Create design tokens', 'Build component library', 'Accessibility audit',
        'Add analytics tracking', 'Implement undo/redo', 'Create developer docs',
        'Review and merge PRs', 'Sprint planning meeting', 'User research sessions',
        'Competitor analysis', 'Update dependencies', 'Run security scan',
        'Load testing', 'Deploy to production', 'Post-mortem writeup', 'Retrospective prep',
    ];

    public function run(): void
    {
        $projects = Project::with(['columns', 'members'])->get();
        $statuses = ['todo', 'in_progress', 'in_review', 'done'];
        $priorities = ['low', 'medium', 'high', 'critical'];
        $titleIndex = 0;

        foreach ($projects as $project) {
            $columns  = $project->columns;
            $members  = $project->members;
            $labels   = Label::where('project_id', $project->id)->get();
            if ($columns->isEmpty() || $members->isEmpty()) continue;

            $taskCount = rand(6, 12);

            for ($i = 0; $i < $taskCount; $i++) {
                $column   = $columns->random();
                $status   = match($column->slug) {
                    'to-do'       => 'todo',
                    'in-progress' => 'in_progress',
                    'in-review'   => 'in_review',
                    'done'        => 'done',
                    default       => $statuses[array_rand($statuses)],
                };

                $title = $this->taskTitles[$titleIndex % count($this->taskTitles)];
                $titleIndex++;

                $task = Task::create([
                    'project_id'       => $project->id,
                    'column_id'        => $column->id,
                    'created_by'       => $project->owner_id,
                    'title'            => $title,
                    'description'      => "This task covers: {$title}. Detailed requirements to be added.",
                    'status'           => $status,
                    'priority'         => $priorities[array_rand($priorities)],
                    'position'         => $i + 1,
                    'story_points'     => [1, 2, 3, 5, 8, 13][array_rand([1,2,3,5,8,13])],
                    'estimated_hours'  => rand(1, 16),
                    'due_date'         => rand(0,1) ? now()->addDays(rand(-10, 30)) : null,
                ]);

                // Assign 1–2 members
                $assignees = $members->random(min(rand(1,2), $members->count()));
                foreach ($assignees as $assignee) {
                    $task->assignees()->syncWithoutDetaching([$assignee->id => ['assigned_by' => $project->owner_id]]);
                }

                // Attach a label randomly
                if ($labels->isNotEmpty() && rand(0,1)) {
                    $task->labels()->syncWithoutDetaching([$labels->random()->id]);
                }
            }
        }
    }
}
