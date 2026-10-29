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
        return view('projects.create');
    }

    public function store(StoreProjectRequest $request)
    {
        $project = $this->projectService->createProject($request->validated(), $request->user());

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project created successfully.');
    }

    public function show(Project $project)
    {
        $this->authorize('view', $project);

        $project->load(['columns.tasks.assignees', 'members', 'owner', 'labels']);
        $stats = $this->projectService->getProjectStats($project);

        return view('projects.show', compact('project', 'stats'));
    }

    public function edit(Project $project)
    {
        $this->authorize('update', $project);
        return view('projects.edit', compact('project'));
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        $this->projectService->updateProject($project, $request->validated());

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
