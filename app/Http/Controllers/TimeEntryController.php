<?php

namespace App\Http\Controllers;

use App\Http\Requests\TimeEntry\StoreTimeEntryRequest;
use App\Http\Requests\TimeEntry\UpdateTimeEntryRequest;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Services\TimeTrackingService;
use Illuminate\Http\Request;

class TimeEntryController extends Controller
{
    public function __construct(private readonly TimeTrackingService $timeTrackingService) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $from = $request->get('from', now()->startOfWeek()->toDateString());
        $to   = $request->get('to', now()->endOfWeek()->toDateString());

        $summary = $this->timeTrackingService->getSummaryForUser($user, now()->parse($from), now()->parse($to));

        return view('time-entries.index', compact('summary', 'from', 'to'));
    }

    public function store(StoreTimeEntryRequest $request)
    {
        $entry = $this->timeTrackingService->createManualEntry($request->validated(), $request->user());

        return response()->json(['entry' => $entry->load(['task', 'project'])]);
    }

    public function update(UpdateTimeEntryRequest $request, TimeEntry $timeEntry)
    {
        $entry = $this->timeTrackingService->updateEntry($timeEntry, $request->validated());

        return response()->json(['entry' => $entry]);
    }

    public function destroy(TimeEntry $timeEntry)
    {
        $this->authorize('delete', $timeEntry);
        $this->timeTrackingService->deleteEntry($timeEntry);

        return response()->json(['success' => true]);
    }

    public function start(Request $request)
    {
        $request->validate(['task_id' => 'required|exists:tasks,id', 'description' => 'nullable|string']);

        $task  = Task::findOrFail($request->task_id);
        $entry = $this->timeTrackingService->startTimer($request->user(), $task, $request->description);

        return response()->json(['entry' => $entry->load(['task.project'])]);
    }

    public function stop(Request $request, TimeEntry $timeEntry)
    {
        $this->authorize('update', $timeEntry);

        $entry = $this->timeTrackingService->stopTimer($timeEntry);

        return response()->json(['entry' => $entry]);
    }

    public function running(Request $request)
    {
        $entry = $this->timeTrackingService->getRunningEntry($request->user());

        return response()->json(['entry' => $entry?->load(['task.project'])]);
    }
}
