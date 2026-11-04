<?php

namespace App\Http\Controllers;

use App\Models\Standup;
use App\Models\StandupItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
            ->select('users.*')
            ->selectRaw('(SELECT COUNT(*) FROM task_assignees ta
                          JOIN tasks t ON ta.task_id = t.id
                          WHERE ta.user_id = users.id
                          AND t.status NOT IN ("done","cancelled","archived")) as open_tasks')
            ->selectRaw('(SELECT COUNT(*) FROM task_assignees ta
                          JOIN tasks t ON ta.task_id = t.id
                          WHERE ta.user_id = users.id
                          AND t.status = "in_progress") as active_tasks')
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
        return view('standups.calendar');
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

        return view('standups.show', compact('day', 'standup', 'user'));
    }

    // Admin/manager: view a member's standup; returns JSON when requested (for panel)
    public function memberDay(string $date, User $user, Request $request)
    {
        abort_unless(auth()->user()->hasRole(['admin', 'manager']), 403);

        $day     = Carbon::parse($date);
        $standup = Standup::whereDate('standup_date', $day)
            ->where('user_id', $user->id)
            ->with('items')
            ->first();

        if ($request->wantsJson()) {
            return response()->json([
                'user'    => $user->only('id', 'name', 'email', 'job_title', 'department'),
                'standup' => $standup ? [
                    'id'       => $standup->id,
                    'blockers' => $standup->blockers,
                    'items'    => $standup->items->map(fn($i) => [
                        'id'          => $i->id,
                        'description' => $i->description,
                        'status'      => $i->status,
                        'sort_order'  => $i->sort_order,
                    ])->values(),
                ] : null,
            ]);
        }

        return view('standups.member-day', compact('day', 'standup', 'user'));
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
                'sort_order'  => $i,
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'standup_id' => $standup->id]);
        }

        return redirect()->route('standups.day', $validated['standup_date'])
            ->with('success', 'Standup saved!');
    }
}
