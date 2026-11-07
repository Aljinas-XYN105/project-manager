<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Expense Report</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; background: #fff; padding: 24px; }
  h1 { font-size: 20px; font-weight: 700; color: #1e293b; margin-bottom: 2px; }
  .subtitle { color: #64748b; font-size: 11px; margin-bottom: 20px; }
  .stats { display: flex; gap: 12px; margin-bottom: 16px; }
  .stat-box { flex: 1; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; }
  .stat-label { font-size: 10px; color: #64748b; }
  .stat-value { font-size: 16px; font-weight: 700; color: #1e293b; }
  .stat-value.green { color: #059669; }
  .stat-value.amber { color: #d97706; }
  .stat-value.red { color: #e11d48; }
  .stat-value.purple { font-size: 14px; color: #7c3aed; }
  .section-title { font-size: 12px; font-weight: 600; color: #475569; margin: 14px 0 6px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
  table { width: 100%; border-collapse: collapse; }
  thead tr { background: #f1f5f9; }
  th { text-align: left; padding: 8px 10px; font-size: 10px; font-weight: 600; color: #475569; border-bottom: 2px solid #e2e8f0; }
  th.right { text-align: right; }
  td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
  td.right { text-align: right; }
  tr:nth-child(even) td { background: #f8fafc; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 600; }
  .badge-approved { background: #d1fae5; color: #065f46; }
  .badge-pending  { background: #fef3c7; color: #92400e; }
  .badge-rejected { background: #fee2e2; color: #991b1b; }
  .cat-table { width: 40%; margin-bottom: 16px; }
  .footer { margin-top: 20px; color: #94a3b8; font-size: 9px; text-align: right; }
</style>
</head>
<body>
  <h1>Expense Report</h1>
  <p class="subtitle">{{ $from->format('M j, Y') }} — {{ $to->format('M j, Y') }} &nbsp;·&nbsp; Generated {{ now()->format('M j, Y H:i') }}</p>

  <div class="stats">
    <div class="stat-box">
      <div class="stat-label">Total Requests</div>
      <div class="stat-value">{{ $summary['total_count'] }}</div>
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
      <div class="stat-label">Approved Total</div>
      <div class="stat-value purple">{{ number_format($summary['total_amount'], 2) }}</div>
    </div>
  </div>

  @if($summary['by_category']->count())
  <p class="section-title">Approved by Category</p>
  <table class="cat-table">
    <thead><tr><th>Category</th><th class="right">Amount</th></tr></thead>
    <tbody>
      @foreach($summary['by_category'] as $cat)
      <tr>
        <td>{{ $cat->category }}</td>
        <td class="right">{{ number_format($cat->total, 2) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif

  <p class="section-title">Expense Details</p>
  <table>
    <thead>
      <tr>
        <th>Employee</th>
        <th>Title</th>
        <th>Category</th>
        <th>Date</th>
        <th class="right">Amount</th>
        <th>Status</th>
        <th>Reviewed By</th>
      </tr>
    </thead>
    <tbody>
      @forelse($expenses as $expense)
      <tr>
        <td><strong>{{ $expense->user->name ?? '—' }}</strong></td>
        <td>{{ Str::limit($expense->title, 40) }}</td>
        <td>{{ $expense->category }}</td>
        <td style="white-space:nowrap">{{ $expense->expense_date->format('M j, Y') }}</td>
        <td class="right">{{ $expense->formatted_amount }}</td>
        <td>
          <span class="badge badge-{{ $expense->status }}">{{ ucfirst($expense->status) }}</span>
        </td>
        <td>{{ $expense->reviewer->name ?? '—' }}</td>
      </tr>
      @empty
      <tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:20px">No records found</td></tr>
      @endforelse
    </tbody>
  </table>

  <p class="footer">Workzen &nbsp;·&nbsp; Confidential</p>
</body>
</html>
