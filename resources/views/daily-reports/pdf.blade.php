<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Daily Standup — {{ $dailyReport->report_date->format('M j, Y') }}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; background: #fff; padding: 28px; }
  h1 { font-size: 20px; font-weight: 700; color: #1e293b; margin-bottom: 2px; }
  .meta { color: #64748b; font-size: 11px; margin-bottom: 20px; }
  .author-row { display: flex; align-items: center; gap: 12px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; margin-bottom: 18px; }
  .author-name { font-weight: 700; font-size: 13px; }
  .author-sub  { color: #64748b; font-size: 10px; margin-top: 2px; }
  .hours-badge { margin-left: auto; text-align: right; }
  .hours-badge .h-num { font-size: 22px; font-weight: 700; }
  .hours-badge .h-label { color: #94a3b8; font-size: 10px; }
  .section { margin-bottom: 16px; }
  .section-title { font-size: 12px; font-weight: 700; color: #334155; border-bottom: 2px solid #e2e8f0; padding-bottom: 4px; margin-bottom: 8px; }
  .prose { font-size: 11px; color: #334155; line-height: 1.6; white-space: pre-wrap; }
  .task-row { border: 1px solid #e2e8f0; border-radius: 6px; padding: 9px 12px; margin-bottom: 6px; }
  .task-title { font-weight: 600; font-size: 11px; }
  .task-project { color: #94a3b8; font-size: 10px; margin-bottom: 4px; }
  .task-desc { font-size: 11px; color: #475569; margin-top: 4px; line-height: 1.5; }
  .task-meta { margin-top: 6px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
  .badge { display: inline-block; padding: 2px 7px; border-radius: 999px; font-size: 10px; font-weight: 600; }
  .badge-blue    { background: #dbeafe; color: #1d4ed8; }
  .badge-amber   { background: #fef3c7; color: #92400e; }
  .badge-green   { background: #d1fae5; color: #065f46; }
  .badge-red     { background: #fee2e2; color: #991b1b; }
  .badge-slate   { background: #f1f5f9; color: #475569; }
  .blocked-note  { color: #ef4444; font-size: 10px; }
  .hours-pill    { color: #6366f1; font-weight: 700; font-size: 10px; }
  .footer { margin-top: 24px; color: #94a3b8; font-size: 9px; text-align: right; border-top: 1px solid #f1f5f9; padding-top: 8px; }
  .two-col { display: flex; gap: 16px; }
  .two-col > div { flex: 1; }
</style>
</head>
<body>

  <h1>Daily Standup Report</h1>
  <p class="meta">{{ $dailyReport->report_date->format('l, F j, Y') }} &nbsp;·&nbsp; Generated {{ now()->format('M j, Y H:i') }}</p>

  <div class="author-row">
    <div>
      <div class="author-name">{{ $dailyReport->user->name }}</div>
      <div class="author-sub">{{ $dailyReport->user->job_title ?? 'Team Member' }}</div>
    </div>
    <div class="hours-badge">
      <div class="h-num">{{ number_format($dailyReport->reportTasks->sum('hours_spent'), 1) }}h</div>
      <div class="h-label">total logged</div>
    </div>
  </div>

  <div class="section">
    <div class="section-title">What I Accomplished</div>
    <p class="prose">{{ $dailyReport->summary }}</p>
  </div>

  @if($dailyReport->reportTasks->count())
  <div class="section">
    <div class="section-title">Task Breakdown</div>
    @foreach($dailyReport->reportTasks as $rt)
    @php
      $statusMap = [
        'in_progress' => ['label' => 'In Progress', 'cls' => 'badge-blue'],
        'in_review'   => ['label' => 'In Review',   'cls' => 'badge-amber'],
        'done'        => ['label' => 'Done',         'cls' => 'badge-green'],
        'blocked'     => ['label' => 'Blocked',      'cls' => 'badge-red'],
        'todo'        => ['label' => 'To Do',        'cls' => 'badge-slate'],
        'qa_testing'  => ['label' => 'QA Testing',   'cls' => 'badge-blue'],
      ];
      $st = $statusMap[$rt->status_update ?? ''] ?? null;
    @endphp
    <div class="task-row">
      @if($rt->task)
      <div class="task-project">{{ $rt->task->project?->name ?? '' }}</div>
      <div class="task-title">{{ $rt->task->title }}</div>
      @else
      <div class="task-title">{{ $rt->task_description }}</div>
      @endif
      @if($rt->task && $rt->task_description !== $rt->task->title)
      <p class="task-desc">{{ $rt->task_description }}</p>
      @endif
      <div class="task-meta">
        <span class="hours-pill">{{ $rt->hours_spent }}h</span>
        @if($st)
        <span class="badge {{ $st['cls'] }}">{{ $st['label'] }}</span>
        @endif
        @if($rt->is_blocked)
        <span class="blocked-note">⚠ Blocked</span>
        @endif
        @if($rt->notes)
        <span style="color:#64748b;font-size:10px">{{ $rt->notes }}</span>
        @endif
      </div>
    </div>
    @endforeach
  </div>
  @endif

  <div class="two-col">
    @if($dailyReport->plan_for_tomorrow)
    <div class="section">
      <div class="section-title">Plan for Tomorrow</div>
      <p class="prose">{{ $dailyReport->plan_for_tomorrow }}</p>
    </div>
    @endif
    @if($dailyReport->blockers)
    <div class="section">
      <div class="section-title">Blockers / Impediments</div>
      <p class="prose" style="color:#ef4444">{{ $dailyReport->blockers }}</p>
    </div>
    @endif
  </div>

  <p class="footer">Workzen &nbsp;·&nbsp; Confidential</p>
</body>
</html>
