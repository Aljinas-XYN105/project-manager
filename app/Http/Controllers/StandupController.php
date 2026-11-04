<?php

namespace App\Http\Controllers;

use App\Models\Standup;
use App\Models\StandupItem;
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

        return view('standups.calendar', compact('firstDay', 'returnDate'));
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

    // Admin/manager: view and manage a member's standup items for a date
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
