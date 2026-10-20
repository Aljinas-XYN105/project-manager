@extends('layouts.app')
@section('title', 'Leave & Holidays')

@section('content')
@include('admin._nav')

<div class="page-header">
    <div>
        <h1 class="page-title">Leave & Holidays</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Leave types and yearly allowances, plus the company holiday calendar.</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('leave.approvals') }}" class="btn-secondary"><i class="bi bi-inbox"></i> Approvals @if($summary['pending'])<span class="ml-1 px-1.5 rounded-full bg-amber-500 text-white text-[10px]">{{ $summary['pending'] }}</span>@endif</a>
        <a href="{{ route('leave.calendar') }}" class="btn-ghost"><i class="bi bi-calendar3"></i> Calendar</a>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    @foreach([
        ['Pending requests', $summary['pending'], 'bi-hourglass-split', 'amber'],
        ['On leave today', $summary['on_leave_today'], 'bi-airplane', 'violet'],
        ['Days approved in '.now()->year, rtrim(rtrim(number_format($summary['approved_year'], 1), '0'), '.') ?: 0, 'bi-calendar-check', 'emerald'],
    ] as [$label, $value, $icon, $color])
    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-{{ $color }}-100 dark:bg-{{ $color }}-900/30 flex items-center justify-center"><i class="bi {{ $icon }} text-{{ $color }}-600 dark:text-{{ $color }}-400"></i></div>
        <div><p class="text-2xl font-bold text-slate-900 dark:text-white leading-none">{{ $value }}</p><p class="text-xs text-slate-500 mt-1">{{ $label }}</p></div>
    </div>
    @endforeach
</div>

@if($errors->any())
<div class="alert-danger mb-4"><i class="bi bi-exclamation-triangle-fill"></i><span>{{ $errors->first() }}</span></div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    {{-- Leave types --}}
    <div class="card overflow-hidden self-start">
        <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800">
            <h2 class="font-semibold text-slate-900 dark:text-white">Leave types</h2>
            <p class="text-xs text-slate-500">Set days per year to 0 for unlimited (e.g. unpaid leave).</p>
        </div>
        @foreach($types as $type)
        <form method="POST" action="{{ route('admin.leave.types.update', $type) }}" x-data="{ edit: false }"
              class="px-5 py-3 border-b border-slate-50 dark:border-slate-800 {{ $type->is_active ? '' : 'opacity-60' }}">
            @csrf @method('PUT')
            <div x-show="!edit" class="flex items-center gap-3">
                <span class="w-3 h-3 rounded-full bg-{{ $type->color }}-500"></span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $type->name }} @unless($type->is_active)<span class="text-xs text-slate-400">(inactive)</span>@endunless</p>
                    <p class="text-xs text-slate-500">
                        {{ $type->is_unlimited ? 'Unlimited' : rtrim(rtrim(number_format($type->days_per_year, 1), '0'), '.').' days / year' }}
                        · {{ $type->is_paid ? 'Paid' : 'Unpaid' }} · {{ $type->used_this_year }} approved this year
                    </p>
                </div>
                <button type="button" @click="edit = true" class="btn-ghost btn-sm"><i class="bi bi-pencil"></i></button>
                <button type="submit" form="del-type-{{ $type->id }}" class="btn-ghost btn-sm text-rose-500" onclick="return confirm('Delete {{ $type->name }}?')"><i class="bi bi-trash"></i></button>
            </div>
            <div x-show="edit" x-cloak class="grid grid-cols-2 sm:grid-cols-6 gap-2 items-end">
                <div class="col-span-2"><label class="form-label text-xs">Name</label><input name="name" value="{{ $type->name }}" required class="form-input text-sm"></div>
                <div><label class="form-label text-xs">Days/yr</label><input type="number" name="days_per_year" value="{{ $type->days_per_year }}" min="0" max="365" step="0.5" required class="form-input text-sm"></div>
                <div><label class="form-label text-xs">Colour</label>
                    <select name="color" class="form-select text-sm">@foreach(\App\Models\LeaveType::COLORS as $c)<option @selected($type->color === $c)>{{ $c }}</option>@endforeach</select>
                </div>
                <div class="space-y-1 text-xs text-slate-600 dark:text-slate-400">
                    <label class="flex items-center gap-1.5"><input type="checkbox" name="is_paid" value="1" @checked($type->is_paid) class="rounded text-violet-600"> Paid</label>
                    <label class="flex items-center gap-1.5"><input type="checkbox" name="is_active" value="1" @checked($type->is_active) class="rounded text-violet-600"> Active</label>
                </div>
                <div class="flex gap-1"><button class="btn-primary btn-sm">Save</button><button type="button" @click="edit = false" class="btn-ghost btn-sm">✕</button></div>
            </div>
        </form>
        <form id="del-type-{{ $type->id }}" method="POST" action="{{ route('admin.leave.types.destroy', $type) }}" class="hidden">@csrf @method('DELETE')</form>
        @endforeach

        <form method="POST" action="{{ route('admin.leave.types.store') }}" class="px-5 py-4 bg-slate-50/60 dark:bg-slate-800/30 grid grid-cols-2 sm:grid-cols-6 gap-2 items-end">
            @csrf
            <div class="col-span-2"><label class="form-label text-xs">New type</label><input name="name" required placeholder="e.g. Parental Leave" class="form-input text-sm"></div>
            <div><label class="form-label text-xs">Days/yr</label><input type="number" name="days_per_year" value="5" min="0" max="365" step="0.5" required class="form-input text-sm"></div>
            <div><label class="form-label text-xs">Colour</label>
                <select name="color" class="form-select text-sm">@foreach(\App\Models\LeaveType::COLORS as $c)<option>{{ $c }}</option>@endforeach</select>
            </div>
            <label class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400 pb-2"><input type="checkbox" name="is_paid" value="1" checked class="rounded text-violet-600"> Paid</label>
            <button class="btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add</button>
        </form>
    </div>

    {{-- Holidays --}}
    <div class="card overflow-hidden self-start">
        <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-slate-900 dark:text-white">Holidays {{ $year }}</h2>
                <p class="text-xs text-slate-500">Holidays aren't counted as leave days.</p>
            </div>
            <div class="flex gap-1">
                <a href="?year={{ $year - 1 }}" class="btn-ghost btn-sm"><i class="bi bi-chevron-left"></i></a>
                <a href="?year={{ $year + 1 }}" class="btn-ghost btn-sm"><i class="bi bi-chevron-right"></i></a>
            </div>
        </div>
        @forelse($holidays as $h)
        <div class="flex items-center gap-3 px-5 py-2.5 border-b border-slate-50 dark:border-slate-800 {{ $h->date->isPast() && ! $h->date->isToday() ? 'opacity-50' : '' }}">
            <span class="w-24 text-sm font-mono text-slate-600 dark:text-slate-400">{{ $h->date->format('D M j') }}</span>
            <span class="flex-1 text-sm text-slate-900 dark:text-white">{{ $h->name }}</span>
            <form method="POST" action="{{ route('admin.holidays.destroy', $h) }}" onsubmit="return confirm('Remove {{ $h->name }}?')">
                @csrf @method('DELETE')
                <button class="btn-ghost btn-sm text-rose-500"><i class="bi bi-trash"></i></button>
            </form>
        </div>
        @empty
        <p class="px-5 py-8 text-center text-sm text-slate-400">No holidays added for {{ $year }}.</p>
        @endforelse
        <form method="POST" action="{{ route('admin.holidays.store') }}" class="px-5 py-4 bg-slate-50/60 dark:bg-slate-800/30 flex flex-wrap gap-2 items-end">
            @csrf
            <div><label class="form-label text-xs">Date</label><input type="date" name="date" required class="form-input text-sm"></div>
            <div class="flex-1 min-w-[10rem]"><label class="form-label text-xs">Name</label><input name="name" required placeholder="e.g. Thanksgiving" class="form-input text-sm"></div>
            <button class="btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add holiday</button>
        </form>
    </div>
</div>
@endsection
