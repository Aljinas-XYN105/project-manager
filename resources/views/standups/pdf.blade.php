<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Standups — {{ \Carbon\Carbon::parse($date)->format('M j, Y') }}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; background: #fff; padding: 28px; }
  h1 { font-size: 20px; font-weight: 700; color: #1e293b; margin-bottom: 2px; }
  .meta { color: #64748b; font-size: 11px; margin-bottom: 24px; }
  .member-block { border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 16px; overflow: hidden; page-break-inside: avoid; }
  .member-header { background: #f8fafc; padding: 10px 14px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; }
  .member-name { font-weight: 700; font-size: 13px; }
  .member-title { color: #64748b; font-size: 10px; margin-top: 1px; }
  .items-table { width: 100%; border-collapse: collapse; }
  .items-table th { text-align: left; padding: 6px 14px; font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .04em; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
  .items-table td { padding: 7px 14px; font-size: 11px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
  .items-table tr:last-child td { border-bottom: none; }
  .badge { display: inline-block; padding: 2px 7px; border-radius: 999px; font-size: 10px; font-weight: 600; }
  .badge-todo        { background: #f1f5f9; color: #475569; }
  .badge-in_progress { background: #dbeafe; color: #1d4ed8; }
  .badge-in_review   { background: #fef3c7; color: #92400e; }
  .badge-done        { background: #d1fae5; color: #065f46; }
  .blockers-row { padding: 8px 14px; background: #fff7ed; border-top: 1px solid #fed7aa; font-size: 10px; color: #9a3412; }
  .not-submitted { padding: 10px 14px; color: #94a3b8; font-size: 11px; font-style: italic; }
  .summary-row { display: flex; gap: 6px; align-items: center; }
  .count-pill { font-size: 10px; font-weight: 600; padding: 2px 7px; border-radius: 999px; background: #ede9fe; color: #7c3aed; }
  .footer { margin-top: 24px; color: #94a3b8; font-size: 9px; text-align: right; border-top: 1px solid #f1f5f9; padding-top: 8px; }
</style>
</head>
<body>

  <h1>Team Standup Report</h1>
  <p class="meta">{{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }} &nbsp;·&nbsp; Generated {{ now()->format('M j, Y H:i') }} &nbsp;·&nbsp; {{ $standups->count() }} / {{ $users->count() }} submitted</p>

  @foreach($users as $user)
  @php $standup = $standups->get($user->id); @endphp
  <div class="member-block">
    <div class="member-header">
      <div>
        <div class="member-name">{{ $user->name }}</div>
        <div class="member-title">{{ $user->job_title ?? 'Team Member' }}</div>
      </div>
      @if($standup)
      <div class="summary-row">
        <span class="count-pill">{{ $standup->items->count() }} items</span>
        @if($standup->blockers)
        <span class="badge" style="background:#fee2e2;color:#991b1b">⚠ Blocker</span>
        @endif
      </div>
      @endif
    </div>

    @if($standup && $standup->items->count())
    <table class="items-table">
      <thead>
        <tr>
          <th style="width:65%">Task / Description</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @foreach($standup->items as $item)
        <tr>
          <td>{{ $item->description }}</td>
          <td><span class="badge badge-{{ $item->status }}">{{ ucfirst(str_replace('_',' ',$item->status)) }}</span></td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @if($standup->blockers)
    <div class="blockers-row"><strong>Blockers:</strong> {{ $standup->blockers }}</div>
    @endif
    @elseif($standup)
    <div class="not-submitted">No items logged.</div>
    @else
    <div class="not-submitted">Not submitted</div>
    @endif
  </div>
  @endforeach

  <p class="footer">Workzen &nbsp;·&nbsp; Confidential</p>
</body>
</html>
