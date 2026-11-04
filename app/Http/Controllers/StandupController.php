<?php

namespace App\Http\Controllers;

use App\Models\Standup;
use App\Models\StandupItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StandupController extends Controller
{
    // Calendar: month view of all standups
    public function index(Request $request)
    {
        $month = $request->input('month')
            ? Carbon::parse($request->input('month') . '-01')
            : now()->startOfMonth();

        $start = $month->copy()->startOfMonth();
        $end   = $month->copy()->endOfMonth();

        // Which dates in this month have at least one standup
        $datesWithStandups = Standup::whereBetween('standup_date', [$start, $end])
            ->selectRaw('standup_date, COUNT(*) as count, COUNT(DISTINCT user_id) as member_count')
            ->groupBy('standup_date')
            ->orderBy('standup_date')
            ->get()
            ->keyBy(fn($r) => $r->standup_date->format('Y-m-d'));

        return view('standups.index', compact('month', 'datesWithStandups'));
    }

    // Day view: list of members who submitted on that date
    public function day(string $date)
    {
        $day = Carbon::parse($date);

        $standups = Standup::whereDate('standup_date', $day)
            ->with(['user', 'items'])
            ->get()
            ->sortBy('user.name');

        return view('standups.day', compact('day', 'standups'));
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

    // Admin/manager: view a specific member's standup on a date
    public function memberDay(string $date, User $user)
    {
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
