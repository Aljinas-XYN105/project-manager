<?php

namespace App\Http\Controllers;

use App\Exports\StandupDayExport;
use App\Exports\StandupRangeExport;
use App\Models\Project;
use App\Models\Standup;
use App\Models\StandupItem;
use App\Models\Task;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class StandupController extends Controller
{
    // Team standup dashboard — card grid; non-managers go to their own form
    public function index(Request $request)
    {
        $authUser = auth()->user();

        if (!$authUser->can('standups.view-team')) {
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

        $users->load('roles');

        return Inertia::render('Standups/Index', [
            'date'            => $date->toDateString(),
            'date_label'      => $date->isToday() ? 'Today, '.$date->format('F j') : $date->format('l, F j'),
            'full_date_label' => $date->format('l, F j, Y'),
            'is_today'        => $date->isToday(),
            'users'           => $users->map(fn ($u) => [
                'id'              => $u->id,
                'name'            => $u->name,
                'avatar_url'      => $u->avatar_url,
                'subtitle'        => $u->job_title ?? $u->department ?? $u->email,
                'roles'           => $u->roles->where('name', '!=', 'member')->map(fn ($r) => $r->name)->values(),
                'has_standup'     => $standups->has($u->id),
                'has_blockers'    => $standups->has($u->id) && (bool) $standups[$u->id]->blockers,
                'todo_count'      => $standups->has($u->id) ? $standups[$u->id]->items->where('status', 'todo')->count() : 0,
                'in_prog_count'   => $standups->has($u->id) ? $standups[$u->id]->items->where('status', 'in_progress')->count() : 0,
                'qa_count'        => $standups->has($u->id) ? $standups[$u->id]->items->where('status', 'qa_testing')->count() : 0,
                'review_count'    => $standups->has($u->id) ? $standups[$u->id]->items->where('status', 'in_review')->count() : 0,
                'done_count'      => $standups->has($u->id) ? $standups[$u->id]->items->where('status', 'done')->count() : 0,
                'standup'         => $standups->has($u->id) ? [
                    'id'       => $standups[$u->id]->id,
                    'blockers' => $standups[$u->id]->blockers,
                    'items'    => $standups[$u->id]->items->map(fn ($item) => [
                        'id'          => $item->id,
                        'task_id'     => $item->task_id,
                        'status'      => $item->status,
                        'description' => $item->description,
                    ]),
                ] : null,
                'member_day_url'  => route('standups.member-day', [$date->toDateString(), $u->id]),
                'show_url'        => route('standups.member-day', [$date->toDateString(), $u->id]),
            ]),
            'can_export'             => auth()->user()->can('standups.view-team'),
            'export_excel_url'       => route('standups.export-excel', ['date' => $date->toDateString()]),
            'export_pdf_url'         => route('standups.export-pdf', ['date' => $date->toDateString()]),
            'export_excel_range_url' => route('standups.export-excel-range'),
            'export_pdf_range_url'   => route('standups.export-pdf-range'),
            'my_standup_url'   => route('standups.show', today()->toDateString()),
            'calendar_url'     => route('standups.calendar'),
            'summary_url'      => route('standups.summary'),
        ]);
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

        $calDays = [];
        $cursor = $startWeek->copy();
        while ($cursor->lte($endWeek)) {
            $dateStr = $cursor->toDateString();
            $calDays[] = [
                'date'         => $dateStr,
                'day'          => $cursor->day,
                'is_today'     => $cursor->isToday(),
                'is_current_month' => $cursor->month === $month,
                'submitters'   => isset($standupsByDate[$dateStr])
                    ? $standupsByDate[$dateStr]->map(fn ($s) => $s->user?->name)->filter()->values()
                    : [],
            ];
            $cursor->addDay();
        }

        return Inertia::render('Standups/Calendar', [
            'year'        => $year,
            'month'       => $month,
            'month_label' => $firstDay->format('F Y'),
            'cal_days'    => $calDays,
            'return_date' => $returnDate,
            'index_url'   => route('standups.index'),
            'prev_url'    => route('standups.calendar', ['year' => $firstDay->copy()->subMonth()->year, 'month' => $firstDay->copy()->subMonth()->month]),
            'next_url'    => route('standups.calendar', ['year' => $firstDay->copy()->addMonth()->year, 'month' => $firstDay->copy()->addMonth()->month]),
        ]);
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
                    'status'      => in_array($t->status, ['todo', 'in_progress', 'in_review', 'qa_testing']) ? $t->status : 'todo',
                ]);
        }

        // Projects the member belongs to (for the quick-create task form)
        $projects = $user->can('standups.view-team')
            ? \App\Models\Project::where('status', 'active')->select('id', 'name', 'slug')->orderBy('name')->get()
            : $user->projects()->where('projects.status', 'active')->select('projects.id', 'projects.name', 'projects.slug')->orderBy('projects.name')->get();

        $seedItems = $standup
            ? $standup->items->map(fn($i) => ['id' => $i->id, 'task_id' => $i->task_id, 'description' => $i->description, 'status' => $i->status])->values()
            : $defaultItems;

        // Carry-over: yesterday's unfinished items (only surfaced when today has no standup)
        $carryoverItems = collect();
        if (!$standup && $day->isToday()) {
            $yesterday = Standup::whereDate('standup_date', $day->copy()->subDay())
                ->where('user_id', $user->id)
                ->with('items')
                ->first();
            if ($yesterday) {
                $carryoverItems = $yesterday->items
                    ->whereNotIn('status', ['done'])
                    ->map(fn($i) => ['task_id' => $i->task_id, 'description' => $i->description, 'status' => $i->status])
                    ->values();
            }
        }

        $members = User::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Standups/Show', [
            'date'            => $day->toDateString(),
            'date_label'      => $day->format('l, F j'),
            'is_today'        => $day->isToday(),
            'user'            => [
                'id'        => $user->id,
                'name'      => $user->name,
                'initials'  => strtoupper(substr($user->name, 0, 2)),
                'job_title' => $user->job_title ?? $user->department ?? '',
            ],
            'standup'         => $standup ? [
                'id'       => $standup->id,
                'blockers' => $standup->blockers,
            ] : null,
            'seed_items'      => $seedItems->values(),
            'carryover_items' => $carryoverItems,
            'projects'        => $projects->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values(),
            'members'         => $members->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])->values(),
            'store_url'       => route('standups.store'),
            'index_url'       => route('standups.index'),
            'history_url'     => route('standups.history'),
        ]);
    }

    // Member: paginated history of their own past standups
    public function history(Request $request)
    {
        $user    = auth()->user();
        $perPage = 10;

        $standups = Standup::where('user_id', $user->id)
            ->with('items')
            ->orderByDesc('standup_date')
            ->paginate($perPage);

        $rows = $standups->getCollection()->map(fn ($s) => [
            'id'         => $s->id,
            'date'       => Carbon::parse($s->standup_date)->toDateString(),
            'date_label' => Carbon::parse($s->standup_date)->format('l, F j, Y'),
            'blockers'   => $s->blockers,
            'items'      => $s->items->map(fn ($i) => [
                'id'          => $i->id,
                'description' => $i->description,
                'status'      => $i->status,
                'task_id'     => $i->task_id,
            ])->values(),
            'show_url'   => route('standups.show', Carbon::parse($s->standup_date)->toDateString()),
        ]);

        return Inertia::render('Standups/History', [
            'standups'  => [
                'data'          => $rows,
                'current_page'  => $standups->currentPage(),
                'last_page'     => $standups->lastPage(),
                'total'         => $standups->total(),
                'per_page'      => $standups->perPage(),
            ],
            'index_url' => route('standups.index'),
            'my_today_url' => route('standups.show', today()->toDateString()),
        ]);
    }

    // Admin/manager: team summary digest for a date range
    public function summary(Request $request)
    {
        abort_unless(auth()->user()->can('standups.view-team'), 403);

        $preset = $request->input('preset', 'today');
        [$from, $to] = match ($preset) {
            'yesterday'  => [Carbon::yesterday(), Carbon::yesterday()],
            'this_week'  => [Carbon::now()->startOfWeek(Carbon::MONDAY), Carbon::now()],
            'last_week'  => [Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY), Carbon::now()->subWeek()->endOfWeek(Carbon::SUNDAY)],
            default      => [Carbon::today(), Carbon::today()], // today
        };

        if ($request->filled('from')) $from = Carbon::parse($request->input('from'))->startOfDay();
        if ($request->filled('to'))   $to   = Carbon::parse($request->input('to'))->endOfDay();

        $standups = Standup::whereBetween('standup_date', [$from->startOfDay(), $to->endOfDay()])
            ->with(['user:id,name,avatar_url', 'items'])
            ->orderByDesc('standup_date')
            ->orderBy('user_id')
            ->get();

        $users = User::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $submittedIds = $standups->pluck('user_id')->unique();
        $missingUsers = $users->whereNotIn('id', $submittedIds)->map(fn ($u) => [
            'id'   => $u->id,
            'name' => $u->name,
        ])->values();

        $digest = $standups->groupBy(fn ($s) => Carbon::parse($s->standup_date)->toDateString())
            ->map(fn ($dayStandups, $date) => [
                'date'       => $date,
                'date_label' => Carbon::parse($date)->format('l, F j'),
                'entries'    => $dayStandups->map(fn ($s) => [
                    'user_name'  => $s->user->name ?? 'Unknown',
                    'avatar_url' => $s->user->avatar_url ?? null,
                    'blockers'   => $s->blockers,
                    'items'      => $s->items->map(fn ($i) => [
                        'description' => $i->description,
                        'status'      => $i->status,
                    ])->values(),
                    'member_day_url' => route('standups.member-day', [Carbon::parse($s->standup_date)->toDateString(), $s->user_id]),
                ])->values(),
            ])->values();

        $statusTotals = [
            'todo'        => $standups->sum(fn ($s) => $s->items->where('status', 'todo')->count()),
            'in_progress' => $standups->sum(fn ($s) => $s->items->where('status', 'in_progress')->count()),
            'in_review'   => $standups->sum(fn ($s) => $s->items->where('status', 'in_review')->count()),
            'qa_testing'  => $standups->sum(fn ($s) => $s->items->where('status', 'qa_testing')->count()),
            'done'        => $standups->sum(fn ($s) => $s->items->where('status', 'done')->count()),
        ];

        return Inertia::render('Standups/Summary', [
            'preset'        => $preset,
            'from'          => $from->toDateString(),
            'to'            => $to->toDateString(),
            'digest'        => $digest,
            'status_totals' => $statusTotals,
            'missing_users' => $missingUsers,
            'total_submitted' => $submittedIds->count(),
            'total_members'   => $users->count(),
            'index_url'     => route('standups.index'),
        ]);
    }

    // Admin/manager: view and manage a member's standup items for a date
    public function memberDay(string $date, User $user)
    {
        abort_unless(auth()->user()->can('standups.view-team'), 403);

        $day     = Carbon::parse($date);
        $standup = Standup::whereDate('standup_date', $day)
            ->where('user_id', $user->id)
            ->with('items')
            ->first();

        $memberItems = $standup
            ? $standup->items->map(fn($i) => ['id' => $i->id, 'task_id' => $i->task_id, 'description' => $i->description, 'status' => $i->status])->values()
            : collect();

        return Inertia::render('Standups/MemberDay', [
            'date'         => $day->toDateString(),
            'date_label'   => $day->format('l, F j, Y'),
            'user'         => [
                'id'       => $user->id,
                'name'     => $user->name,
                'initials' => strtoupper(substr($user->name, 0, 2)),
            ],
            'standup'      => $standup ? [
                'id'       => $standup->id,
                'blockers' => $standup->blockers,
            ] : null,
            'member_items' => $memberItems->values(),
            'index_url'    => route('standups.index'),
            'delete_url'   => $standup ? route('standups.destroy', $standup->id) : null,
        ]);
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
            'items.*.status'      => 'required|in:todo,in_progress,in_review,qa_testing,done',
            'items.*.task_id'     => 'nullable|integer|exists:tasks,id',
        ]);

        $authUser = auth()->user();

        // Managers/admins may save on behalf of another user
        if (!empty($validated['user_id']) && $authUser->can('standups.manage')) {
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

    // Admin/manager: delete a submitted standup
    public function destroy(Standup $standup)
    {
        abort_unless(auth()->user()->can('standups.manage'), 403);
        $date = Carbon::parse($standup->standup_date)->toDateString();
        $standup->delete(); // items cascade via FK
        return redirect()->route('standups.member-day', [$date, $standup->user_id])
            ->with('success', 'Standup deleted.');
    }

    public function taskSearch(Request $request)
    {
        $user  = auth()->user();
        $term  = trim((string) $request->input('q', ''));

        $accessibleIds = Project::query()
            ->when(! $user->can('standups.view-team'), fn ($q) => $q->where(fn ($q) => $q
                ->where('is_public', true)
                ->orWhere('owner_id', $user->id)
                ->orWhereHas('members', fn ($m) => $m->where('users.id', $user->id))))
            ->pluck('id');

        $tasks = Task::with('project')
            ->whereIn('project_id', $accessibleIds)
            ->whereNotIn('status', ['done', 'cancelled', 'archived'])
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

    public function exportExcel(Request $request)
    {
        $this->authorizeExport();

        $date     = $request->input('date', today()->toDateString());
        $standups = Standup::whereDate('standup_date', $date)
            ->with(['user', 'items'])
            ->orderBy('user_id')
            ->get();

        return Excel::download(new StandupDayExport($standups, $date), 'standups-'.$date.'.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $this->authorizeExport();

        $date = $request->input('date', today()->toDateString());

        $users = User::where('is_active', true)->orderBy('name')->get();

        $standups = Standup::whereDate('standup_date', $date)
            ->with(['user', 'items'])
            ->get()
            ->keyBy('user_id');

        $pdf = Pdf::loadView('standups.pdf', compact('date', 'users', 'standups'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('standups-'.$date.'.pdf');
    }

    public function exportExcelRange(Request $request)
    {
        $this->authorizeExport();

        [$from, $to] = $this->rangeFromRequest($request);

        $standups = Standup::whereBetween('standup_date', [$from, $to])
            ->with(['user', 'items'])
            ->orderBy('standup_date')
            ->orderBy('user_id')
            ->get();

        $label = $from->format('Y-m-d').'_to_'.$to->format('Y-m-d');

        return Excel::download(new StandupRangeExport($standups, $from, $to), "standups-{$label}.xlsx");
    }

    public function exportPdfRange(Request $request)
    {
        $this->authorizeExport();

        [$from, $to] = $this->rangeFromRequest($request);

        $users = User::where('is_active', true)->orderBy('name')->get();

        $standups = Standup::whereBetween('standup_date', [$from, $to])
            ->with(['user', 'items'])
            ->get();

        // Group by date string → keyed by user_id
        $days = $standups
            ->groupBy(fn ($s) => Carbon::parse($s->standup_date)->toDateString())
            ->map(fn ($group) => $group->keyBy('user_id'));

        $pdf = Pdf::loadView('standups.range-pdf', [
            'from'  => $from->toDateString(),
            'to'    => $to->toDateString(),
            'users' => $users,
            'days'  => $days,
        ])->setPaper('a4', 'portrait');

        $label = $from->format('Y-m-d').'_to_'.$to->format('Y-m-d');

        return $pdf->download("standups-{$label}.pdf");
    }

    private function rangeFromRequest(Request $request): array
    {
        $preset = $request->input('preset', 'this_week');

        $from = match ($preset) {
            'last_week'  => Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY),
            'this_month' => Carbon::now()->startOfMonth(),
            'last_month' => Carbon::now()->subMonth()->startOfMonth(),
            default      => Carbon::now()->startOfWeek(Carbon::MONDAY), // this_week
        };

        $to = match ($preset) {
            'last_week'  => Carbon::now()->subWeek()->endOfWeek(Carbon::SUNDAY),
            'this_month' => Carbon::now()->endOfMonth(),
            'last_month' => Carbon::now()->subMonth()->endOfMonth(),
            default      => Carbon::now()->endOfWeek(Carbon::SUNDAY),
        };

        // Allow explicit from/to to override preset
        if ($request->filled('from')) $from = Carbon::parse($request->input('from'))->startOfDay();
        if ($request->filled('to'))   $to   = Carbon::parse($request->input('to'))->endOfDay();

        return [$from, $to];
    }

    private function authorizeExport(): void
    {
        if (!auth()->user()->can('standups.view-team')) {
            abort(403);
        }
    }
}
