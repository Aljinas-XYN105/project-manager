@extends('layouts.app')
@section('title', 'Expense Report')

@section('content')
<div class="space-y-6">
    <div class="page-header">
        <div>
            <h1 class="page-title">Expense Report</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                {{ $from->format('M j, Y') }} — {{ $to->format('M j, Y') }}
            </p>
        </div>
        <a href="{{ route('reports.expenses', array_merge(request()->query(), ['export' => 'pdf'])) }}"
           class="btn-secondary flex items-center gap-2">
            <i class="bi bi-file-earmark-pdf"></i> Export PDF
        </a>
    </div>

    {{-- Filters --}}
    <div class="card p-4">
        <form method="GET" action="{{ route('reports.expenses') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="form-label">From</label>
                <input type="date" name="from" value="{{ request('from', $from->format('Y-m-d')) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">To</label>
                <input type="date" name="to" value="{{ request('to', $to->format('Y-m-d')) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Employee</label>
                <x-ui.select name="user_id" placeholder="All Employees"
                    :value="request('user_id','')"
                    :options="$users->pluck('name','id')->toArray()" />
            </div>
            <div>
                <label class="form-label">Category</label>
                <x-ui.select name="category" placeholder="All Categories"
                    :value="request('category','')"
                    :options="array_combine($categories, $categories)" />
            </div>
            <div>
                <label class="form-label">Status</label>
                <x-ui.select name="status" placeholder="All Statuses"
                    :value="request('status','')"
                    :options="['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected']" />
            </div>
            <button type="submit" class="btn-primary">Apply Filters</button>
            <a href="{{ route('reports.expenses') }}" class="btn-secondary">Reset</a>
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        <div class="stat-card">
            <span class="text-sm text-slate-500 dark:text-slate-400">Total Requests</span>
            <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ $summary['total_count'] }}</div>
        </div>
        <div class="stat-card">
            <span class="text-sm text-slate-500 dark:text-slate-400">Approved</span>
            <div class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $summary['approved'] }}</div>
        </div>
        <div class="stat-card">
            <span class="text-sm text-slate-500 dark:text-slate-400">Pending</span>
            <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ $summary['pending'] }}</div>
        </div>
        <div class="stat-card">
            <span class="text-sm text-slate-500 dark:text-slate-400">Rejected</span>
            <div class="text-3xl font-bold text-rose-600 dark:text-rose-400">{{ $summary['rejected'] }}</div>
        </div>
        <div class="stat-card">
            <span class="text-sm text-slate-500 dark:text-slate-400">Approved Total</span>
            <div class="text-2xl font-bold text-violet-600 dark:text-violet-400">
                {{ number_format($summary['total_amount'], 2) }}
            </div>
        </div>
    </div>

    {{-- By Category --}}
    @if($summary['by_category']->count())
    <div class="card">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="font-semibold text-slate-900 dark:text-white">Approved by Category</h2>
        </div>
        <div class="divide-y divide-slate-100 dark:divide-slate-700">
            @foreach($summary['by_category'] as $cat)
            <div class="px-4 py-3 flex items-center justify-between">
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $cat->category }}</span>
                <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ number_format($cat->total, 2) }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Table --}}
    <div class="card">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700">
                        <th class="text-left px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Employee</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Title</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Category</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Date</th>
                        <th class="text-right px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Amount</th>
                        <th class="text-center px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Status</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">Reviewed By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($expenses as $expense)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                        <td class="px-4 py-3 font-medium text-slate-800 dark:text-slate-200">{{ $expense->user->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $expense->title }}</td>
                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $expense->category }}</td>
                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ $expense->expense_date->format('M j, Y') }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-700 dark:text-slate-300">{{ $expense->formatted_amount }}</td>
                        <td class="px-4 py-3 text-center">
                            @php $st = \App\Models\Expense::STATUSES[$expense->status] ?? null @endphp
                            @if($st)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $st['class'] }}">
                                {{ $st['label'] }}
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $expense->reviewer->name ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-slate-400">No expenses found for the selected filters.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($expenses->hasPages())
        <div class="p-4 border-t border-slate-100 dark:border-slate-700">
            {{ $expenses->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
