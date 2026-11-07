<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(private readonly ProjectService $projectService) {}

    public function index(Request $request)
    {
        $projects = $this->projectService->getProjectsForUser($request->user(), $request->only([
            'status', 'priority', 'search',
        ]));

        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        $this->authorize('create', Project::class);
        $users = User::active()->orderBy('name')->get(['id', 'name', 'job_title', 'avatar']);
        return view('projects.create', compact('users'));
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

        return view('projects.show', compact('project', 'stats'));
    }

    public function edit(Project $project)
    {
        $this->authorize('update', $project);
        $users = User::active()->orderBy('name')->get(['id', 'name', 'job_title', 'avatar']);
        $project->load('members');
        return view('projects.edit', compact('project', 'users'));
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
