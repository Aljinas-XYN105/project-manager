<?php

namespace App\Http\Controllers;

use App\Http\Requests\TimeEntry\StoreTimeEntryRequest;
use App\Http\Requests\TimeEntry\UpdateTimeEntryRequest;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Services\TimeTrackingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TimeEntryController extends Controller
{
    public function __construct(private readonly TimeTrackingService $timeTrackingService) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $from = $request->get('from', now()->startOfWeek()->toDateString());
        $to   = $request->get('to', now()->endOfWeek()->toDateString());

        $summary = $this->timeTrackingService->getSummaryForUser($user, now()->parse($from), now()->parse($to));

        $entries = $summary['entries'];

        $grouped = $entries->groupBy(fn ($e) => $e->started_at->toDateString())
            ->map(fn ($dayEntries) => $dayEntries->map(fn ($e) => [
                'id'              => $e->id,
                'description'     => $e->description,
                'task_title'      => $e->task?->title,
                'project_name'    => $e->task?->project?->name ?? $e->project?->name,
                'elapsed_seconds' => $e->elapsed_seconds,
                'paused_seconds'  => $e->paused_seconds ?? 0,
                'status'          => $e->status,
                'started_at_ts'   => $e->started_at?->getTimestamp(),
                'is_live'         => in_array($e->status, ['running', 'paused']),
                'update_url'      => route('time-entries.update', $e),
                'destroy_url'     => route('time-entries.destroy', $e),
                'stop_url'        => route('time-entries.stop', $e),
                'pause_url'       => route('time-entries.pause', $e),
                'resume_url'      => route('time-entries.resume', $e),
            ])->values())->sortKeysDesc();

        return Inertia::render('TimeEntries/Index', [
            'grouped'     => $grouped,
            'from'        => $from,
            'to'          => $to,
            'total_hours' => $summary['total_hours'],
            'entry_count' => $entries->count(),
        ]);
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

        return response()->json(['entry' => $this->formatEntry($entry->load(['task.project']))]);
    }

    public function pause(Request $request, TimeEntry $timeEntry)
    {
        $this->authorize('update', $timeEntry);
        $entry = $this->timeTrackingService->pauseTimer($timeEntry);
        return response()->json(['entry' => $this->formatEntry($entry->load(['task.project']))]);
    }

    public function resume(Request $request, TimeEntry $timeEntry)
    {
        $this->authorize('update', $timeEntry);
        $entry = $this->timeTrackingService->resumeTimer($timeEntry);
        return response()->json(['entry' => $this->formatEntry($entry->load(['task.project']))]);
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
        return response()->json(['entry' => $entry ? $this->formatEntry($entry) : null]);
    }

    public function active(Request $request)
    {
        $entries = $this->timeTrackingService->getActiveEntries($request->user());
        return response()->json([
            'entries' => $entries->map(fn($e) => $this->formatEntry($e)),
            'count'   => $entries->count(),
            'running' => $entries->where('status', 'running')->count(),
            'paused'  => $entries->where('status', 'paused')->count(),
        ]);
    }

    private function formatEntry(TimeEntry $entry): array
    {
        return [
            'id'              => $entry->id,
            'status'          => $entry->status,
            'task_id'         => $entry->task_id,
            'task_title'      => $entry->task?->title,
            'project_name'    => $entry->task?->project?->name,
            'project_color'   => $entry->task?->project?->color ?? '#6366f1',
            'started_at'      => $entry->started_at?->toIso8601String(),
            'paused_seconds'  => $entry->paused_seconds ?? 0,
            'elapsed_seconds' => $entry->elapsed_seconds,
            'description'     => $entry->description,
        ];
    }
}
