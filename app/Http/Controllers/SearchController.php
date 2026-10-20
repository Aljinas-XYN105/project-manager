<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $term  = trim((string) $request->input('q'));
        $limit = $request->expectsJson() ? 5 : 30;

        $results = ['projects' => collect(), 'tasks' => collect(), 'people' => collect()];

        if (mb_strlen($term) >= 2) {
            $projects = $this->accessibleProjects($request->user());
            $results  = [
                'projects' => $projects->filter(fn (Project $p) => str_contains(mb_strtolower($p->name.' '.$p->description), mb_strtolower($term)))
                    ->take($limit)->values(),
                'tasks'    => $this->searchTasks($term, $projects, $limit),
                'people'   => User::active()
                    ->where(fn ($q) => $q->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('job_title', 'like', "%{$term}%")
                        ->orWhere('department', 'like', "%{$term}%"))
                    ->orderBy('name')->limit($limit)->get(),
            ];
        }

        if ($request->expectsJson()) {
            return response()->json([
                'projects' => $results['projects']->map(fn ($p) => [
                    'title' => $p->name, 'subtitle' => ucfirst(str_replace('_', ' ', $p->status)),
                    'url' => route('projects.show', $p), 'color' => $p->color,
                ]),
                'tasks' => $results['tasks']->map(fn ($t) => [
                    'title' => $t->title, 'subtitle' => $t->ticket_key.' · '.$t->project->name.' · '.ucfirst(str_replace('_', ' ', $t->status)),
                    'url' => route('projects.tasks.show', $t),
                ]),
                'people' => $results['people']->map(fn ($u) => [
                    'title' => $u->name, 'subtitle' => $u->job_title ?: $u->email,
                    'url' => route('users.show', $u), 'avatar' => $u->avatar_url,
                ]),
            ]);
        }

        return view('search.index', ['term' => $term] + $results);
    }

    private function accessibleProjects(User $user)
    {
        return Project::query()
            ->when(! $user->hasRole('admin'), fn ($q) => $q->where(fn ($q) => $q
                ->where('is_public', true)
                ->orWhere('owner_id', $user->id)
                ->orWhereHas('members', fn ($m) => $m->where('users.id', $user->id))))
            ->orderBy('name')
            ->get();
    }

    private function searchTasks(string $term, $projects, int $limit)
    {
        $ids = $projects->pluck('id');

        // Ticket keys like "MOBILE-12" map to a project prefix + ticket number.
        if (preg_match('/^([A-Za-z0-9]+)-(\d+)$/', $term, $m)) {
            $project = $projects->first(fn ($p) => strcasecmp($p->key_prefix, $m[1]) === 0);
            if ($project) {
                return Task::with('project')->where('project_id', $project->id)
                    ->where('ticket_number', (int) $m[2])->limit($limit)->get();
            }
        }

        return Task::with('project')
            ->whereIn('project_id', $ids)
            ->where(fn ($q) => $q->where('title', 'like', "%{$term}%")->orWhere('description', 'like', "%{$term}%"))
            ->orderByRaw("case when status in ('done','cancelled') then 1 else 0 end")
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }
}
