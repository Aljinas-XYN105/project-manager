@extends('layouts.app')
@section('title', $role->exists ? 'Edit role' : 'New role')

@section('content')
@php $isAdminRole = $role->name === 'admin'; @endphp
<div class="max-w-3xl mx-auto">
    <div class="page-header">
        <div>
            <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                <a href="{{ route('admin.roles.index') }}" class="hover:text-violet-600">Roles & Permissions</a>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span>{{ $role->exists ? Str::headline($role->name) : 'New' }}</span>
            </div>
            <h1 class="page-title">{{ $role->exists ? 'Edit role' : 'Create role' }}</h1>
        </div>
        <a href="{{ route('admin.roles.index') }}" class="btn-ghost"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <form method="POST" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}"
          x-data="{ selected: @js(old('permissions', $selected)) }" class="space-y-5">
        @csrf
        @if($role->exists) @method('PUT') @endif

        <div class="card p-6">
            <label class="form-label">Role name *</label>
            <input type="text" name="name" value="{{ old('name', $role->name) }}" required
                   {{ $isAdminRole ? 'readonly' : '' }} pattern="[a-z0-9_]+"
                   class="form-input font-mono {{ $isAdminRole ? 'bg-slate-50 dark:bg-slate-800 cursor-not-allowed' : '' }}"
                   placeholder="e.g. team_lead">
            <p class="form-hint">Lowercase letters, numbers and underscores. Shown to users as "{{ Str::headline(old('name', $role->name) ?: 'team_lead') }}".</p>
            @error('name')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="card overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-slate-900 dark:text-white">Permissions</h2>
                    <p class="text-xs text-slate-500" x-text="selected.length + ' selected'"></p>
                </div>
                @unless($isAdminRole)
                <div class="flex gap-2">
                    <button type="button" class="btn-ghost btn-sm" @click="selected = @js($permissionGroups->flatten()->pluck('name'))">Select all</button>
                    <button type="button" class="btn-ghost btn-sm" @click="selected = []">Clear</button>
                </div>
                @endunless
            </div>

            @if($isAdminRole)
            <div class="px-6 py-4 bg-rose-50 dark:bg-rose-900/10 text-sm text-rose-700 dark:text-rose-400 flex items-center gap-2">
                <i class="bi bi-shield-lock-fill"></i> The admin role always has every permission so it can never be locked out.
            </div>
            @else
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach($permissionGroups as $group => $perms)
                @php $names = $perms->pluck('name'); @endphp
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between mb-2.5">
                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ Str::headline($group) }}</p>
                        <button type="button" class="text-xs text-violet-600 hover:underline"
                                @click="const g = @js($names); const all = g.every(p => selected.includes(p));
                                        selected = all ? selected.filter(p => !g.includes(p)) : [...new Set([...selected, ...g])]">
                            Toggle group
                        </button>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        @foreach($perms as $perm)
                        <label class="flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer
                                      hover:border-violet-400 has-[:checked]:border-violet-500 has-[:checked]:bg-violet-50 dark:has-[:checked]:bg-violet-900/20">
                            <input type="checkbox" name="permissions[]" value="{{ $perm->name }}" x-model="selected"
                                   class="rounded text-violet-600 border-slate-300 focus:ring-violet-500">
                            <span class="text-xs text-slate-700 dark:text-slate-300">{{ Str::headline(Str::after($perm->name, '.')) }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.roles.index') }}" class="btn-ghost">Cancel</a>
            <button class="btn-primary"><i class="bi bi-check-lg"></i> {{ $role->exists ? 'Save role' : 'Create role' }}</button>
        </div>
    </form>
</div>
@endsection
