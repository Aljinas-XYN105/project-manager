<?php

namespace App\Services;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TeamService
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function createTeam(array $data, User $owner): Team
    {
        return DB::transaction(function () use ($data, $owner) {
            $data['owner_id'] = $owner->id;
            $team = Team::create($data);

            TeamMember::create([
                'team_id'   => $team->id,
                'user_id'   => $owner->id,
                'role'      => 'owner',
                'joined_at' => now(),
            ]);

            $this->activityLogService->log("created team \"{$team->name}\"", $team, $owner);

            return $team;
        });
    }

    public function updateTeam(Team $team, array $data): Team
    {
        $team->update($data);
        $this->activityLogService->log("updated team \"{$team->name}\"", $team, auth()->user());
        return $team->fresh();
    }

    public function deleteTeam(Team $team): bool
    {
        return $team->delete();
    }

    public function addMember(Team $team, User $user, string $role = 'member'): TeamMember
    {
        $member = TeamMember::updateOrCreate(
            ['team_id' => $team->id, 'user_id' => $user->id],
            ['role' => $role, 'joined_at' => now()]
        );

        $this->activityLogService->log("added {$user->name} to team as {$role}", $team, auth()->user());

        return $member;
    }

    public function removeMember(Team $team, User $user): void
    {
        TeamMember::where('team_id', $team->id)->where('user_id', $user->id)->delete();
        $this->activityLogService->log("removed {$user->name} from team", $team, auth()->user());
    }

    public function updateMemberRole(Team $team, User $user, string $role): void
    {
        TeamMember::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->update(['role' => $role]);
    }

    public function getTeamsForUser(User $user): Collection
    {
        $query = Team::with(['owner', 'teamMembers.user']);

        // Admins see all teams
        if ($user->hasRole('admin')) {
            return $query->orderBy('name')->get();
        }

        return $query->whereHas('users', fn($q) => $q->where('users.id', $user->id))
            ->get();
    }
}
