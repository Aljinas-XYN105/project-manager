<?php

namespace App\Http\Controllers;

use App\Http\Requests\Team\StoreTeamRequest;
use App\Models\Team;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function __construct(private readonly TeamService $teamService) {}

    public function index(Request $request)
    {
        $teams = $this->teamService->getTeamsForUser($request->user());
        return view('teams.index', compact('teams'));
    }

    public function create()
    {
        $this->authorize('create', Team::class);
        return view('teams.create');
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
        return view('teams.show', compact('team'));
    }

    public function edit(Team $team)
    {
        $this->authorize('update', $team);
        return view('teams.edit', compact('team'));
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
