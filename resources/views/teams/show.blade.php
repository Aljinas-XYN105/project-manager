@extends('layouts.app')
@section('title', $team->name)
@section('content')
<div class="page-header">
    <h1 class="page-title">{{ $team->name }}</h1>
    @can('manageMember', $team)
    <a href="{{ route('teams.edit', $team) }}" class="btn-secondary btn-sm"><i class="bi bi-pencil"></i> Edit</a>
    @endcan
</div>
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <h3 class="font-semibold text-slate-900 dark:text-white">Members ({{ $team->teamMembers->count() }})</h3>
            @can('manageMember', $team)
            <button x-data @click="$dispatch('open-add-member')" class="btn-primary btn-sm"><i class="bi bi-person-plus"></i> Add</button>
            @endcan
        </div>
        <div class="divide-y divide-slate-50 dark:divide-slate-700">
            @foreach($team->teamMembers as $member)
            <div class="flex items-center gap-4 px-5 py-3.5">
                <img src="{{ $member->user?->avatar_url ?? asset('images/default-avatar.png') }}" class="w-9 h-9 rounded-full">
                <div class="flex-1">
                    <p class="font-medium text-sm text-slate-900 dark:text-white">{{ $member->user?->name ?? '—' }}</p>
                    <p class="text-xs text-slate-500">{{ $member->user?->email ?? '—' }}</p>
                </div>
                <span class="badge {{ $member->role === 'owner' ? 'bg-violet-100 text-violet-700' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($member->role) }}</span>
                @can('manageMember', $team)
                @if($member->user_id !== $team->owner_id)
                <form method="POST" action="{{ route('teams.members.remove', [$team, $member->user]) }}">
                    @csrf @method('DELETE')
                    <button class="text-slate-300 hover:text-red-500 transition-colors"><i class="bi bi-x-lg text-sm"></i></button>
                </form>
                @endif
                @endcan
            </div>
            @endforeach
        </div>
    </div>
    <div class="card p-5">
        <h3 class="font-semibold text-slate-900 dark:text-white mb-3">Projects</h3>
        @if($team->projects->count())
        <div class="space-y-2">
            @foreach($team->projects as $project)
            <a href="{{ route('projects.show', $project) }}" class="flex items-center gap-2 p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <div class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $project->color }}"></div>
                <span class="text-sm text-slate-700 dark:text-slate-300 flex-1 truncate">{{ $project->name }}</span>
                <span class="badge-{{ $project->status }} text-[10px]">{{ ucfirst(str_replace('_',' ',$project->status)) }}</span>
            </a>
            @endforeach
        </div>
        @else
        <p class="text-xs text-slate-400">No projects yet</p>
        @endif
    </div>
</div>
@can('manageMember', $team)
<div x-data="{ open: false }" x-on:open-add-member.window="open = true">
<template x-teleport="body">
<div x-show="open" x-cloak
     @keydown.escape.window="open = false"
     class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
    <div @click="open = false"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="absolute inset-0 backdrop-blur-sm" style="background-color:rgb(15 23 42/.6)"></div>
    <div x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4"
         class="relative card w-full max-w-md overflow-hidden flex flex-col max-h-[90dvh]">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex-shrink-0">
            <h3 class="font-semibold text-slate-900 dark:text-white">Add member</h3>
            <button type="button" @click="open = false" class="p-1 -mr-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                <i class="bi bi-x-lg text-base"></i>
            </button>
        </div>
        @if($availableUsers->isEmpty())
        <div class="px-6 py-8 text-center">
            <p class="text-sm text-slate-500">All users are already in this team.</p>
        </div>
        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 flex justify-end flex-shrink-0">
            <button type="button" @click="open = false" class="btn-ghost">Close</button>
        </div>
        @else
        <form method="POST" action="{{ route('teams.members.add', $team) }}" class="contents">
            @csrf
            <div class="px-6 py-5 space-y-4 overflow-y-auto">
                <div>
                    <label class="form-label">Member</label>
                    <x-ui.select name="user_id" placeholder="Select a user…"
                        required
                        :options="$availableUsers->mapWithKeys(fn($u)=>[$u->id=>$u->name.' ('.$u->email.')'])->toArray()" />
                </div>
                <div>
                    <label class="form-label">Role</label>
                    <x-ui.select name="role"
                        value="member"
                        required
                        :options="['member'=>'Member','admin'=>'Admin','viewer'=>'Viewer']" />
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 flex justify-end gap-2 flex-shrink-0">
                <button type="button" @click="open = false" class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary">Add member</button>
            </div>
        </form>
        @endif
    </div>
</div>
</template>
</div>
@endcan
@endsection
