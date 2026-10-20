<?php

namespace App\Services;

use App\Contracts\Repositories\ProjectRepositoryInterface;
use App\Models\Column;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function createProject(array $data, User $owner): Project
    {
        return DB::transaction(function () use ($data, $owner) {
            $data['owner_id'] = $owner->id;
            $project = $this->projectRepository->create($data);

            $this->createDefaultColumns($project);

            $project->members()->attach($owner->id, ['role' => 'manager']);

            $this->activityLogService->log(
                "created project \"{$project->name}\"",
                $project,
                $owner,
                ['status' => $project->status]
            );

            return $project;
        });
    }

    public function updateProject(Project $project, array $data): Project
    {
        $updated = $this->projectRepository->update($project, $data);

        $this->activityLogService->log(
            "updated project \"{$updated->name}\"",
            $updated,
            auth()->user(),
            ['changes' => array_keys($data)]
        );

        Cache::forget("project_stats_{$project->id}");

        return $updated;
    }

    public function deleteProject(Project $project): bool
    {
        $this->activityLogService->log(
            "deleted project \"{$project->name}\"",
            $project,
            auth()->user()
        );
        return $this->projectRepository->delete($project);
    }

    public function archiveProject(Project $project): Project
    {
        return $this->updateProject($project, ['status' => 'archived']);
    }

    public function restoreProject(Project $project): Project
    {
        return $this->updateProject($project, ['status' => 'active']);
    }

    public function addMember(Project $project, User $user, string $role = 'developer'): void
    {
        $project->members()->syncWithoutDetaching([$user->id => ['role' => $role]]);

        $this->activityLogService->log(
            "added {$user->name} to project as {$role}",
            $project,
            auth()->user()
        );
    }

    public function removeMember(Project $project, User $user): void
    {
        $project->members()->detach($user->id);

        $this->activityLogService->log(
            "removed {$user->name} from project",
            $project,
            auth()->user()
        );
    }

    public function updateMemberRole(Project $project, User $user, string $role): void
    {
        $project->members()->updateExistingPivot($user->id, ['role' => $role]);
    }

    public function getProjectsForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->projectRepository->forUser($user->id, $filters);
    }

    public function getProjectStats(Project $project): array
    {
        return Cache::remember("project_stats_{$project->id}", 300, function () use ($project) {
            $tasks = $project->tasks()->whereNull('parent_id');

            return [
                'total_tasks'       => $tasks->count(),
                'todo'              => $tasks->clone()->where('status', 'todo')->count(),
                'in_progress'       => $tasks->clone()->where('status', 'in_progress')->count(),
                'in_review'         => $tasks->clone()->where('status', 'in_review')->count(),
                'done'              => $tasks->clone()->where('status', 'done')->count(),
                'overdue'           => $tasks->clone()->overdue()->count(),
                'progress'          => $project->progress_percentage,
                'total_hours'       => $project->total_tracked_hours,
                'members_count'     => $project->members()->count(),
            ];
        });
    }

    public function createDefaultColumns(Project $project): void
    {
        $defaults = [
            ['name' => 'To Do',      'slug' => 'todo',        'color' => '#6B7280', 'position' => 0, 'is_default' => true,  'is_done' => false],
            ['name' => 'In Progress','slug' => 'in-progress', 'color' => '#3B82F6', 'position' => 1, 'is_default' => false, 'is_done' => false],
            ['name' => 'In Review',  'slug' => 'in-review',   'color' => '#F59E0B', 'position' => 2, 'is_default' => false, 'is_done' => false],
            ['name' => 'Done',       'slug' => 'done',        'color' => '#10B981', 'position' => 3, 'is_default' => false, 'is_done' => true],
        ];

        foreach ($defaults as $col) {
            Column::create(array_merge($col, ['project_id' => $project->id]));
        }
    }
}
