<?php

namespace App\Http\Controllers;

use App\Models\Standup;
use App\Models\StandupItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StandupController extends Controller
{
    // Team standup dashboard — today's list by default; date picker navigates past days
    public function index(Request $request)
    {
        $authUser = auth()->user();

        // Non-managers go straight to their own standup form
        if (!$authUser->hasRole(['admin', 'manager'])) {
            return redirect()->route('standups.show', today()->toDateString());
        }

        $date = $request->input('date')
            ? Carbon::parse($request->input('date'))
            : Carbon::today();

        $users = \App\Models\User::where('is_active', true)
            ->orderBy('name')
            ->get();

        $standups = Standup::whereDate('standup_date', $date)
            ->with('items')
            ->get()
            ->keyBy('user_id');

        return view('standups.index', compact('date', 'users', 'standups'));
    }

    // Unused legacy route — kept for backward compat redirect
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

    // Admin/manager: Kanban view of a member's standup on a date
    public function memberDay(string $date, User $user)
    {
        abort_unless(auth()->user()->hasRole(['admin', 'manager']), 403);

        $day     = Carbon::parse($date);
        $standup = Standup::whereDate('standup_date', $day)
            ->where('user_id', $user->id)
            ->with('items')
            ->first();

        return view('standups.member-day', compact('day', 'standup', 'user'));
    }

    // Save/update standup for today (or given date)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'standup_date' => 'required|date',
            'blockers'     => 'nullable|string|max:2000',
            'items'        => 'nullable|array',
            'items.*.description' => 'required|string|max:500',
            'items.*.status'      => 'required|in:todo,in_progress,in_review,done',
        ]);

        $user    = auth()->user();
        $standup = Standup::updateOrCreate(
            ['user_id' => $user->id, 'standup_date' => $validated['standup_date']],
            ['blockers' => $validated['blockers'] ?? null]
        );

        // Replace all items
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
