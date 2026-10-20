@extends('layouts.app')
@section('title', 'Roles & Permissions')

@section('content')
@include('admin._nav')

<div class="page-header">
    <div>
        <h1 class="page-title">Roles & Permissions</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Roles bundle permissions. Assign roles to members on the Members page.</p>
    </div>
    <a href="{{ route('admin.roles.create') }}" class="btn-primary"><i class="bi bi-plus-lg"></i> New role</a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    {{-- Roles --}}
    <div class="xl:col-span-2 card overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800">
            <h2 class="font-semibold text-slate-900 dark:text-white">Roles <span class="text-slate-400 font-normal">({{ $roles->count() }})</span></h2>
        </div>
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @foreach($roles as $role)
            <div class="flex items-center gap-4 px-5 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/40">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 {{ $role->name === 'admin' ? 'bg-rose-100 dark:bg-rose-900/30' : 'bg-violet-100 dark:bg-violet-900/30' }}">
                    <i class="bi {{ $role->name === 'admin' ? 'bi-shield-fill-check text-rose-600 dark:text-rose-400' : 'bi-key-fill text-violet-600 dark:text-violet-400' }}"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-slate-900 dark:text-white">{{ Str::headline($role->name) }}</p>
                    <p class="text-xs text-slate-500">
                        <a href="{{ route('users.index', ['role' => $role->name]) }}" class="hover:text-violet-600">{{ $role->users_count }} {{ Str::plural('member', $role->users_count) }}</a>
                        · {{ $role->name === 'admin' ? 'All permissions' : $role->permissions_count.' '.Str::plural('permission', $role->permissions_count) }}
                    </p>
                </div>
                <a href="{{ route('admin.roles.edit', $role) }}" class="btn-ghost btn-sm"><i class="bi bi-pencil"></i> Edit</a>
                @if($role->name !== 'admin')
                <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('Delete the {{ $role->name }} role?')">
                    @csrf @method('DELETE')
                    <button class="btn-ghost btn-sm text-rose-500" title="Delete role"><i class="bi bi-trash"></i></button>
                </form>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    {{-- Permissions --}}
    <div class="card overflow-hidden self-start">
        <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800">
            <h2 class="font-semibold text-slate-900 dark:text-white">Permissions</h2>
            <p class="text-xs text-slate-500 mt-0.5">New permissions are granted to admin automatically.</p>
        </div>
        <form method="POST" action="{{ route('admin.permissions.store') }}" class="p-4 border-b border-slate-100 dark:border-slate-800 flex gap-2">
            @csrf
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="area.action, e.g. invoices.view" class="form-input text-sm flex-1">
            <button class="btn-primary btn-sm"><i class="bi bi-plus-lg"></i></button>
        </form>
        @error('name')<p class="px-4 pt-2 form-error">{{ $message }}</p>@enderror
        <div class="max-h-[32rem] overflow-y-auto">
            @foreach($permissionGroups as $group => $perms)
            <div x-data="{ open: false }" class="border-b border-slate-50 dark:border-slate-800 last:border-b-0">
                <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-5 py-2.5 text-left hover:bg-slate-50 dark:hover:bg-slate-800/40">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ Str::headline($group) }}</span>
                    <span class="flex items-center gap-2 text-xs text-slate-400">{{ $perms->count() }} <i class="bi bi-chevron-down transition-transform" :class="open && 'rotate-180'"></i></span>
                </button>
                <div x-show="open" x-collapse x-cloak class="px-5 pb-3 space-y-1">
                    @foreach($perms as $perm)
                    <div class="flex items-center justify-between group">
                        <code class="text-xs text-slate-600 dark:text-slate-400">{{ $perm->name }}</code>
                        <form method="POST" action="{{ route('admin.permissions.destroy', $perm) }}" onsubmit="return confirm('Delete permission {{ $perm->name }}? It will be removed from every role.')">
                            @csrf @method('DELETE')
                            <button class="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-rose-500 transition-opacity" title="Delete permission"><i class="bi bi-x-lg text-xs"></i></button>
                        </form>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
