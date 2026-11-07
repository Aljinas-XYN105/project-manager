<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Leave Report</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; background: #fff; padding: 24px; }
  h1 { font-size: 20px; font-weight: 700; color: #1e293b; margin-bottom: 2px; }
  .subtitle { color: #64748b; font-size: 11px; margin-bottom: 20px; }
  .stats { display: flex; gap: 12px; margin-bottom: 20px; }
  .stat-box { flex: 1; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; }
  .stat-label { font-size: 10px; color: #64748b; }
  .stat-value { font-size: 18px; font-weight: 700; color: #1e293b; }
  .stat-value.green { color: #059669; }
  .stat-value.amber { color: #d97706; }
  .stat-value.red { color: #e11d48; }
  .stat-value.purple { color: #7c3aed; }
  table { width: 100%; border-collapse: collapse; margin-top: 4px; }
  thead tr { background: #f1f5f9; }
  th { text-align: left; padding: 8px 10px; font-size: 10px; font-weight: 600; color: #475569; border-bottom: 2px solid #e2e8f0; }
  td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
  tr:nth-child(even) td { background: #f8fafc; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 600; }
  .badge-approved { background: #d1fae5; color: #065f46; }
  .badge-pending  { background: #fef3c7; color: #92400e; }
  .badge-rejected { background: #fee2e2; color: #991b1b; }
  .badge-cancelled { background: #f1f5f9; color: #475569; }
  .footer { margin-top: 20px; color: #94a3b8; font-size: 9px; text-align: right; }
</style>
</head>
<body>
  <h1>Leave Report</h1>
  <p class="subtitle">{{ $from->format('M j, Y') }} — {{ $to->format('M j, Y') }} &nbsp;·&nbsp; Generated {{ now()->format('M j, Y H:i') }}</p>

  <div class="stats">
    <div class="stat-box">
      <div class="stat-label">Total Requests</div>
      <div class="stat-value">{{ $summary['total'] }}</div>
    </div>
    <div class="stat-box">
      <div class="stat-label">Approved</div>
      <div class="stat-value green">{{ $summary['approved'] }}</div>
    </div>
    <div class="stat-box">
      <div class="stat-label">Pending</div>
      <div class="stat-value amber">{{ $summary['pending'] }}</div>
    </div>
    <div class="stat-box">
      <div class="stat-label">Rejected</div>
      <div class="stat-value red">{{ $summary['rejected'] }}</div>
    </div>
    <div class="stat-box">
      <div class="stat-label">Approved Days</div>
      <div class="stat-value purple">{{ number_format($summary['days'], 1) }}</div>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th>Employee</th>
        <th>Type</th>
        <th>Dates</th>
        <th>Days</th>
        <th>Reason</th>
        <th>Status</th>
        <th>Reviewed By</th>
        <th>Reviewed At</th>
      </tr>
    </thead>
    <tbody>
      @forelse($leaves as $leave)
      <tr>
        <td><strong>{{ $leave->user->name ?? '—' }}</strong></td>
        <td>{{ $leave->type->name ?? '—' }}</td>
        <td style="white-space:nowrap">{{ $leave->date_range }}</td>
        <td style="text-align:center">{{ $leave->days }}</td>
        <td>{{ Str::limit($leave->reason ?? '—', 60) }}</td>
        <td>
          <span class="badge badge-{{ $leave->status }}">{{ ucfirst($leave->status) }}</span>
        </td>
        <td>{{ $leave->reviewer->name ?? '—' }}</td>
        <td style="white-space:nowrap">{{ $leave->reviewed_at ? $leave->reviewed_at->format('M j, Y') : '—' }}</td>
      </tr>
      @empty
      <tr><td colspan="8" style="text-align:center;color:#94a3b8;padding:20px">No records found</td></tr>
      @endforelse
    </tbody>
  </table>

  <p class="footer">Workzen &nbsp;·&nbsp; Confidential</p>
</body>
</html>
