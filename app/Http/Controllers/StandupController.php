<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Standup;
use App\Models\StandupItem;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StandupController extends Controller
{
    // Team standup dashboard — card grid; non-managers go to their own form
    public function index(Request $request)
    {
        $authUser = auth()->user();

        if (!$authUser->hasRole(['admin', 'manager'])) {
            return redirect()->route('standups.show', today()->toDateString());
        }

        $date = $request->input('date')
            ? Carbon::parse($request->input('date'))
            : Carbon::today();

        $users = User::where('is_active', true)
            ->orderBy('name')
            ->get();

        $standups = Standup::whereDate('standup_date', $date)
            ->with('items')
            ->get()
            ->keyBy('user_id');

        return view('standups.index', compact('date', 'users', 'standups'));
    }

    // Full-page calendar for date navigation
    public function calendar(Request $request)
    {
        $year       = (int) $request->input('year',  now()->year);
        $month      = (int) $request->input('month', now()->month);
        $firstDay   = Carbon::create($year, $month, 1);
        $returnDate = $request->input('return_date');

        $startWeek = $firstDay->copy()->startOfWeek(Carbon::SUNDAY);
        $endWeek   = $firstDay->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        // Load submission names per day for the visible range
        $standupsByDate = Standup::whereBetween('standup_date', [$startWeek, $endWeek])
            ->with('user:id,name')
            ->get()
            ->groupBy(fn ($s) => $s->standup_date instanceof Carbon
                ? $s->standup_date->toDateString()
                : Carbon::parse($s->standup_date)->toDateString()
            );

        return view('standups.calendar', compact('firstDay', 'returnDate', 'standupsByDate'));
    }

    // Legacy redirect — kept for backward compat
    public function day(string $date)
    {
        return redirect()->route('standups.index', ['date' => $date]);
    }

    // Show or create form for the authenticated user's standup on a date
    public function show(string $date)
    {
        $day     = Carbon::parse($date);
        $user    = auth()->user();
        $standup = Standup::whereDate('standup_date', $day)
            ->where('user_id', $user->id)
            ->with('items')
            ->first();

        // Pre-populate from assigned tasks when no standup exists for today
        $defaultItems = collect();
        if (!$standup && $day->isToday()) {
            $defaultItems = $user->assignedTasks()
                ->whereNotIn('status', ['cancelled', 'archived', 'done'])
                ->get()
                ->map(fn ($t) => [
                    'id'          => null,
                    'task_id'     => $t->id,
                    'description' => "[{$t->ticket_key}] {$t->title}",
                    'status'      => in_array($t->status, ['todo', 'in_progress', 'in_review']) ? $t->status : 'todo',
                ]);
        }

        // Projects the member belongs to (for the quick-create task form)
        $projects = $user->hasRole(['admin', 'manager'])
            ? \App\Models\Project::where('status', 'active')->select('id', 'name', 'slug')->orderBy('name')->get()
            : $user->projects()->where('projects.status', 'active')->select('projects.id', 'projects.name', 'projects.slug')->orderBy('projects.name')->get();

        $seedItems = $standup
            ? $standup->items->map(fn($i) => ['id' => $i->id, 'task_id' => $i->task_id, 'description' => $i->description, 'status' => $i->status])->values()
            : $defaultItems;

        $members = User::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('standups.show', compact('day', 'standup', 'user', 'defaultItems', 'seedItems', 'projects', 'members'));
    }

    // Admin/manager: view and manage a member's standup items for a date
    public function memberDay(string $date, User $user)
    {
        abort_unless(auth()->user()->hasRole(['admin', 'manager']), 403);

        $day     = Carbon::parse($date);
        $standup = Standup::whereDate('standup_date', $day)
            ->where('user_id', $user->id)
            ->with('items')
            ->first();

        $memberItems = $standup
            ? $standup->items->map(fn($i) => ['id' => $i->id, 'task_id' => $i->task_id, 'description' => $i->description, 'status' => $i->status])->values()
            : collect();

        return view('standups.member-day', compact('day', 'standup', 'user', 'memberItems'));
    }

    // Save/update standup; managers may pass user_id to save on a member's behalf
    public function store(Request $request)
    {
        $validated = $request->validate([
            'standup_date' => 'required|date',
            'user_id'      => 'nullable|integer|exists:users,id',
            'blockers'     => 'nullable|string|max:2000',
            'items'        => 'nullable|array',
            'items.*.description' => 'required|string|max:500',
            'items.*.status'      => 'required|in:todo,in_progress,in_review,done',
            'items.*.task_id'     => 'nullable|integer|exists:tasks,id',
        ]);

        $authUser = auth()->user();

        // Managers/admins may save on behalf of another user
        if (!empty($validated['user_id']) && $authUser->hasRole(['admin', 'manager'])) {
            $targetUserId = $validated['user_id'];
        } else {
            $targetUserId = $authUser->id;
        }

        $standup = Standup::updateOrCreate(
            ['user_id' => $targetUserId, 'standup_date' => $validated['standup_date']],
            ['blockers' => $validated['blockers'] ?? null]
        );

        $standup->items()->delete();
        foreach ($validated['items'] ?? [] as $i => $item) {
            $standup->items()->create([
                'description' => $item['description'],
                'status'      => $item['status'],
                'task_id'     => $item['task_id'] ?? null,
                'sort_order'  => $i,
            ]);

            // Keep the actual task status in sync when it has a linked task
            if (!empty($item['task_id'])) {
                \App\Models\Task::where('id', $item['task_id'])
                    ->whereNotIn('status', ['done', 'cancelled'])
                    ->update(['status' => $item['status']]);
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'standup_id' => $standup->id]);
        }

        return redirect()->route('standups.day', $validated['standup_date'])
            ->with('success', 'Standup saved!');
    }

    public function taskSearch(Request $request)
    {
        $user  = auth()->user();
        $term  = trim((string) $request->input('q', ''));

        $accessibleIds = Project::query()
            ->when(! $user->hasRole(['admin', 'manager']), fn ($q) => $q->where(fn ($q) => $q
                ->where('is_public', true)
                ->orWhere('owner_id', $user->id)
                ->orWhereHas('members', fn ($m) => $m->where('users.id', $user->id))))
            ->pluck('id');

        $tasks = Task::with('project')
            ->whereIn('project_id', $accessibleIds)
            ->whereNotIn('status', ['done', 'cancelled'])
            ->when(mb_strlen($term) >= 2, fn ($q) => $q->where('title', 'like', "%{$term}%"))
            ->when(mb_strlen($term) < 2, fn ($q) => $q->whereHas('assignees', fn ($a) => $a->where('users.id', $user->id)))
            ->orderByRaw("case when status = 'in_progress' then 0 when status = 'todo' then 1 else 2 end")
            ->latest('updated_at')
            ->limit(10)
            ->get();

        return response()->json($tasks->map(fn ($t) => [
            'id'          => $t->id,
            'title'       => $t->title,
            'ticket_key'  => $t->ticket_key,
            'project'     => $t->project->name ?? '',
            'status'      => $t->status,
            'label'       => ($t->ticket_key ? "[{$t->ticket_key}] " : '') . $t->title,
        ]));
    }
}
