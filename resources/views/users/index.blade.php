@extends('layouts.app')
@section('title', 'Members')

@section('content')
@php
    $canManage = auth()->user()->hasRole('admin') || auth()->user()->can('users.manage');
    $statuses  = \App\Models\User::AVAILABILITY;
@endphp

@if($canManage) @include('admin._nav') @endif

<div class="page-header">
    <div>
        <h1 class="page-title">Members</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{{ $counts['total'] }} members · {{ $counts['online'] }} online now</p>
    </div>
    @if($canManage)
    <a href="{{ route('users.create') }}" class="btn-primary"><i class="bi bi-person-plus"></i> Add member</a>
    @endif
</div>

{{-- Filters --}}
<form method="GET" class="card p-4 mb-4 flex flex-wrap items-end gap-3">
    <div class="flex-1 min-w-[12rem]">
        <label class="form-label">Search</label>
        <input type="search" name="search" value="{{ request('search') }}" class="form-input" placeholder="Name or email…">
    </div>
    <div>
        <label class="form-label">Role</label>
        <select name="role" class="form-select">
            <option value="">All roles</option>
            @foreach($roles as $role)
            <option value="{{ $role->name }}" @selected(request('role') === $role->name)>{{ Str::headline($role->name) }} ({{ $role->users_count }})</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label">Status</label>
        <select name="active" class="form-select">
            <option value="">All ({{ $counts['total'] }})</option>
            <option value="1" @selected(request('active') === '1')>Active ({{ $counts['active'] }})</option>
            <option value="0" @selected(request('active') === '0')>Inactive ({{ $counts['inactive'] }})</option>
        </select>
    </div>
    <button class="btn-primary"><i class="bi bi-funnel"></i> Filter</button>
    @if(request()->hasAny(['search','role','active']))
    <a href="{{ route('users.index') }}" class="btn-ghost">Clear</a>
    @endif
</form>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 dark:border-slate-700 text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-5 py-3 font-medium">Member</th>
                    <th class="px-5 py-3 font-medium">Roles</th>
                    <th class="px-5 py-3 font-medium">Availability</th>
                    <th class="px-5 py-3 font-medium">Account</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($users as $user)
                @php $st = $statuses[$user->effective_availability] ?? $statuses['offline']; @endphp
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 {{ $user->is_active ? '' : 'opacity-60' }}">
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="relative flex-shrink-0">
                                <img src="{{ $user->avatar_url }}" class="w-9 h-9 rounded-full" alt="">
                                <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full ring-2 ring-white dark:ring-slate-900 {{ $st['dot'] }}"></span>
                            </div>
                            <div class="min-w-0">
                                <a href="{{ route('users.show', $user) }}" class="font-medium text-slate-900 dark:text-white hover:text-violet-600">{{ $user->name }}</a>
                                <p class="text-xs text-slate-500 truncate">{{ $user->email }}@if($user->job_title) · {{ $user->job_title }}@endif</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3.5">
                        <div class="flex flex-wrap gap-1">
                            @forelse($user->roles as $role)
                            <span class="text-[11px] px-2 py-0.5 rounded-full font-medium {{ $role->name === 'admin' ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400' : 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400' }}">{{ Str::headline($role->name) }}</span>
                            @empty
                            <span class="text-xs text-slate-400">No role</span>
                            @endforelse
                        </div>
                    </td>
                    <td class="px-5 py-3.5">
                        <p class="text-xs font-medium text-slate-700 dark:text-slate-300">{{ $st['label'] }}</p>
                        <p class="text-[11px] text-slate-400">{{ $user->last_seen_at ? 'Seen '.$user->last_seen_at->diffForHumans() : 'Never signed in' }}</p>
                    </td>
                    <td class="px-5 py-3.5">
                        @if($user->is_active)
                        <span class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 font-medium">Active</span>
                        @else
                        <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 font-medium">Inactive</span>
                        @endif
                    </td>
                    <td class="px-5 py-3.5 text-right whitespace-nowrap">
                        <a href="{{ route('users.show', $user) }}" class="btn-ghost btn-sm" title="Profile"><i class="bi bi-eye"></i></a>
                        @if(auth()->user()->hasRole('admin'))
                        <a href="{{ route('admin.employee', $user) }}" class="btn-ghost btn-sm" title="Performance"><i class="bi bi-graph-up"></i></a>
                        @endif
                        @if($canManage)
                        <a href="{{ route('users.edit', $user) }}" class="btn-ghost btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>
                        @unless($user->is(auth()->user()))
                        <form method="POST" action="{{ route('users.toggle-active', $user) }}" class="inline">
                            @csrf @method('PATCH')
                            <button class="btn-ghost btn-sm {{ $user->is_active ? 'text-amber-600' : 'text-emerald-600' }}" title="{{ $user->is_active ? 'Deactivate' : 'Activate' }}">
                                <i class="bi {{ $user->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                            </button>
                        </form>
                        @endunless
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">No members match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div class="p-4 border-t border-slate-100 dark:border-slate-800">{{ $users->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
