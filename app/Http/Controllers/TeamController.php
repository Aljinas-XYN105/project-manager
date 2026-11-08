<?php

namespace App\Http\Controllers;

use App\Http\Requests\Team\StoreTeamRequest;
use App\Models\Team;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TeamController extends Controller
{
    public function __construct(private readonly TeamService $teamService) {}

    public function index(Request $request)
    {
        $teams = $this->teamService->getTeamsForUser($request->user());
        $canCreate = $request->user()->can('create', Team::class);

        return Inertia::render('Teams/Index', [
            'teams'      => $teams->map(fn ($t) => [
                'id'          => $t->id,
                'name'        => $t->name,
                'description' => $t->description,
                'member_count'=> $t->teamMembers->count(),
                'users'       => $t->users->take(5)->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'avatar_url' => $u->avatar_url]),
                'extra_users' => max(0, $t->users->count() - 5),
                'show_url'    => route('teams.show', $t),
                'edit_url'    => route('teams.edit', $t),
                'can_edit'    => $request->user()->can('update', $t),
            ]),
            'can_create' => $canCreate,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Team::class);
        return Inertia::render('Teams/Create', [
            'store_url' => route('teams.store'),
            'back_url'  => route('teams.index'),
        ]);
    }

    public function store(StoreTeamRequest $request)
    {
        $team = $this->teamService->createTeam($request->validated(), $request->user());
        return redirect()->route('teams.show', $team)->with('success', 'Team created.');
    }

    public function show(Team $team)
    {
        $this->authorize('view', $team);
        $team->load(['owner', 'teamMembers.user', 'projects']);
        $memberIds = $team->teamMembers->pluck('user_id');
        $availableUsers = User::whereNotIn('id', $memberIds)->orderBy('name')->get();
        $authUser = request()->user();

        return Inertia::render('Teams/Show', [
            'team' => [
                'id'          => $team->id,
                'name'        => $team->name,
                'description' => $team->description,
                'owner_id'    => $team->owner_id,
                'members'     => $team->teamMembers->map(fn ($m) => [
                    'id'         => $m->id,
                    'user_id'    => $m->user_id,
                    'name'       => $m->user?->name ?? '—',
                    'email'      => $m->user?->email ?? '—',
                    'avatar_url' => $m->user?->avatar_url ?? asset('images/default-avatar.png'),
                    'role'       => $m->role,
                    'remove_url' => $m->user ? route('teams.members.remove', [$team, $m->user]) : null,
                ]),
                'projects'    => $team->projects->map(fn ($p) => [
                    'id'       => $p->id,
                    'name'     => $p->name,
                    'color'    => $p->color,
                    'status'   => $p->status,
                    'show_url' => route('projects.show', $p),
                ]),
                'edit_url'       => route('teams.edit', $team),
                'add_member_url' => route('teams.members.add', $team),
                'can_manage'     => $authUser->can('manageMember', $team),
            ],
            'available_users' => $availableUsers->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email]),
        ]);
    }

    public function edit(Team $team)
    {
        $this->authorize('update', $team);
        return Inertia::render('Teams/Edit', [
            'team' => [
                'id'          => $team->id,
                'name'        => $team->name,
                'description' => $team->description ?? $team->settings['description'] ?? '',
                'can_delete'  => request()->user()->can('delete', $team),
                'update_url'  => route('teams.update', $team),
                'destroy_url' => route('teams.destroy', $team),
                'show_url'    => route('teams.show', $team),
            ],
        ]);
    }

    public function update(Request $request, Team $team)
    {
        $this->authorize('update', $team);
        $request->validate(['name' => 'required|string|max:255', 'description' => 'nullable|string']);
        $this->teamService->updateTeam($team, $request->validated());
        return redirect()->route('teams.show', $team)->with('success', 'Team updated.');
    }

    public function destroy(Team $team)
    {
        $this->authorize('delete', $team);
        $this->teamService->deleteTeam($team);
        return redirect()->route('teams.index')->with('success', 'Team deleted.');
    }

    public function addMember(Request $request, Team $team)
    {
        $this->authorize('manageMember', $team);
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role'    => 'required|in:admin,member,viewer',
        ]);
        $user = User::findOrFail($request->user_id);
        $this->teamService->addMember($team, $user, $request->role);
        return back()->with('success', "{$user->name} added to team.");
    }

    public function removeMember(Team $team, User $user)
    {
        $this->authorize('manageMember', $team);
        $this->teamService->removeMember($team, $user);
        return back()->with('success', 'Member removed.');
    }
}
