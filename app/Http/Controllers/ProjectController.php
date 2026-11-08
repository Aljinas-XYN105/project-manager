<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function __construct(private readonly ProjectService $projectService) {}

    public function list(Request $request)
    {
        $projects = $request->user()->projects()
            ->where('status', '!=', 'archived')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($projects);
    }

    public function index(Request $request)
    {
        $projects = $this->projectService->getProjectsForUser($request->user(), $request->only([
            'status', 'priority', 'search',
        ]));

        return Inertia::render('Projects/Index', [
            'projects' => $projects->through(fn ($p) => [
                'id'                  => $p->id,
                'name'                => $p->name,
                'description'         => $p->description,
                'color'               => $p->color,
                'status'              => $p->status,
                'priority'            => $p->priority,
                'due_date'            => $p->due_date?->format('M j, Y'),
                'is_overdue'          => $p->is_overdue,
                'progress_percentage' => $p->progress_percentage,
                'members_count'       => $p->members->count() + 1,
                'owner'               => $p->owner ? ['name' => $p->owner->name] : null,
                'url'                 => route('projects.show', $p),
                'board_url'           => route('projects.tasks.kanban', $p),
                'tasks_url'           => route('projects.tasks.index', $p),
                'edit_url'            => route('projects.edit', $p),
                'sprints_url'         => route('projects.sprints.index', $p),
                'archive_url'         => route('projects.archive', $p),
                'can_archive'         => $request->user()->can('archive', $p),
            ]),
            'filters' => $request->only(['search', 'status', 'priority']),
            'can_create' => $request->user()->can('create', Project::class),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Project::class);
        $users = User::active()->orderBy('name')->get(['id', 'name', 'job_title', 'avatar']);
        return Inertia::render('Projects/Create', [
            'users' => $users->map(fn ($u) => [
                'id'        => $u->id,
                'name'      => $u->name,
                'job_title' => $u->job_title ?? '',
                'avatar'    => $u->avatar ?? null,
            ]),
        ]);
    }

    public function store(StoreProjectRequest $request)
    {
        $data = $request->validated();
        $memberIds = collect($data['member_ids'] ?? [])->map(fn($id) => (int) $id)->unique();
        $memberRoles = $data['member_roles'] ?? [];
        unset($data['member_ids'], $data['member_roles']);

        $project = $this->projectService->createProject($data, $request->user());

        foreach ($memberIds as $userId) {
            if ($userId === $request->user()->id) continue; // owner already added as manager
            $role = $memberRoles[$userId] ?? 'developer';
            $this->projectService->addMember($project, User::findOrFail($userId), $role);
        }

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project created successfully.');
    }

    public function show(Project $project)
    {
        $this->authorize('view', $project);

        $project->load(['columns.tasks.assignees', 'members', 'owner', 'labels', 'attachments.user']);
        $stats = $this->projectService->getProjectStats($project);

        $memberIds = $project->members->pluck('id');
        $availableUsers = User::active()->orderBy('name')
            ->whereNotIn('id', $memberIds)
            ->get(['id', 'name', 'job_title', 'avatar']);

        $user = request()->user();

        return Inertia::render('Projects/Show', [
            'project' => [
                'id'          => $project->id,
                'name'        => $project->name,
                'description' => $project->description,
                'color'       => $project->color,
                'status'      => $project->status,
                'priority'    => $project->priority,
                'due_date'    => $project->due_date?->format('M j, Y'),
                'is_overdue'  => $project->is_overdue,
                'is_public'   => $project->is_public,
                'owner_id'    => $project->owner_id,
                'owner'       => $project->owner ? ['name' => $project->owner->name] : null,
                'members'     => $project->members->map(fn ($m) => [
                    'id'        => $m->id,
                    'name'      => $m->name,
                    'avatar_url'=> $m->avatar_url,
                    'role'      => $m->pivot->role,
                ]),
                'attachments' => $project->attachments->map(fn ($a) => [
                    'id'             => $a->id,
                    'original_name'  => $a->original_name,
                    'mime_type'      => $a->mime_type,
                    'size_formatted' => $a->size_formatted,
                    'is_image'       => $a->is_image,
                    'is_video'       => $a->is_video,
                    'url'            => $a->url,
                    'uploader_name'  => $a->user?->name ?? '—',
                    'created_ago'    => $a->created_at->diffForHumans(),
                ]),
                'notes_url'   => route('projects.notes', $project),
                'sprints_url' => route('projects.sprints.index', $project),
                'board_url'   => route('projects.tasks.kanban', $project),
                'tasks_url'   => route('projects.tasks.index', $project),
                'add_task_url'=> route('projects.tasks.create', $project),
                'edit_url'    => route('projects.edit', $project),
                'add_member_url'    => route('projects.members.add', $project),
                'destroy_attachment_url' => route('attachments.destroy', ':id'),
                'upload_attachment_url'  => route('attachments.project.store', $project),
            ],
            'stats' => $stats,
            'available_users' => $availableUsers->map(fn ($u) => [
                'id'   => $u->id,
                'name' => $u->name,
            ]),
            'can_update'       => $user->can('update', $project),
            'can_manage_member'=> $user->can('manageMember', $project),
        ]);
    }

    public function edit(Project $project)
    {
        $this->authorize('update', $project);
        $users = User::active()->orderBy('name')->get(['id', 'name', 'job_title', 'avatar']);
        $project->load('members');
        return Inertia::render('Projects/Edit', [
            'project' => [
                'id'          => $project->id,
                'name'        => $project->name,
                'description' => $project->description,
                'color'       => $project->color ?? '#3B82F6',
                'status'      => $project->status,
                'priority'    => $project->priority,
                'start_date'  => $project->start_date?->format('Y-m-d'),
                'due_date'    => $project->due_date?->format('Y-m-d'),
                'budget'      => $project->budget,
                'is_public'   => $project->is_public,
                'owner_id'    => $project->owner_id,
                'members'     => $project->members->map(fn ($m) => [
                    'id'        => $m->id,
                    'name'      => $m->name,
                    'job_title' => $m->job_title ?? '',
                    'avatar'    => $m->avatar ?? null,
                    'role'      => $m->pivot->role ?? 'developer',
                ]),
                'update_url'  => route('projects.update', $project),
                'show_url'    => route('projects.show', $project),
            ],
            'users' => $users->map(fn ($u) => [
                'id'        => $u->id,
                'name'      => $u->name,
                'job_title' => $u->job_title ?? '',
                'avatar'    => $u->avatar ?? null,
            ]),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        $data = $request->validated();
        $memberIds = collect($data['member_ids'] ?? [])->map(fn($id) => (int) $id)->unique();
        $memberRoles = $data['member_roles'] ?? [];
        unset($data['member_ids'], $data['member_roles']);

        $this->projectService->updateProject($project, $data);

        // Sync members: keep owner as manager, sync the rest
        $ownerId = $project->owner_id;
        $syncData = [];
        foreach ($memberIds as $userId) {
            if ($userId === $ownerId) continue;
            $syncData[$userId] = ['role' => $memberRoles[$userId] ?? 'developer'];
        }
        // Always keep owner
        $syncData[$ownerId] = ['role' => 'manager'];
        $project->members()->sync($syncData);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project updated.');
    }

    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);
        $this->projectService->deleteProject($project);

        return redirect()->route('projects.index')
            ->with('success', 'Project deleted.');
    }

    public function archive(Project $project)
    {
        $this->authorize('archive', $project);
        $this->projectService->archiveProject($project);

        return back()->with('success', 'Project archived.');
    }

    public function restore(Project $project)
    {
        $this->authorize('update', $project);
        $this->projectService->restoreProject($project);

        return back()->with('success', 'Project restored.');
    }

    public function addMember(Request $request, Project $project)
    {
        $this->authorize('manageMember', $project);

        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role'    => ['required', 'in:manager,developer,viewer'],
        ]);

        $user = User::findOrFail($request->user_id);
        $this->projectService->addMember($project, $user, $request->role);

        return back()->with('success', "{$user->name} added to project.");
    }

    public function removeMember(Request $request, Project $project, User $user)
    {
        $this->authorize('manageMember', $project);
        $this->projectService->removeMember($project, $user);

        return back()->with('success', 'Member removed.');
    }
}
