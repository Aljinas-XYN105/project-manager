@extends('layouts.app')
@section('title', 'Teams')
@section('content')
<div class="page-header">
    <h1 class="page-title">Teams</h1>
    @can('create', App\Models\Team::class)
    <a href="{{ route('teams.create') }}" class="btn-primary"><i class="bi bi-plus-lg"></i> New Team</a>
    @endcan
</div>
@if($teams->count())
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    @foreach($teams as $team)
    <div class="card-hover p-5">
        <div class="flex items-start justify-between gap-3 mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-violet-100 dark:bg-violet-900/30 rounded-xl flex items-center justify-center">
                    <i class="bi bi-people-fill text-violet-600 dark:text-violet-400"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-white">{{ $team->name }}</h3>
                    <p class="text-xs text-slate-500">{{ $team->teamMembers->count() }} member{{ $team->teamMembers->count() !== 1 ? 's' : '' }}</p>
                </div>
            </div>
            @can('update', $team)
            <a href="{{ route('teams.edit', $team) }}" class="text-slate-300 hover:text-slate-600 dark:hover:text-slate-200"><i class="bi bi-pencil text-sm"></i></a>
            @endcan
        </div>
        @if($team->description)
        <p class="text-xs text-slate-500 mb-4 line-clamp-2">{{ $team->description }}</p>
        @endif
        <div class="flex items-center justify-between">
            <div class="flex">
                @foreach($team->users->take(5) as $u)
                <img src="{{ $u->avatar_url }}" title="{{ $u->name }}" class="w-7 h-7 rounded-full border-2 border-white dark:border-slate-800 -ml-1.5 first:ml-0">
                @endforeach
                @if($team->users->count() > 5)
                <span class="w-7 h-7 rounded-full bg-slate-200 text-xs flex items-center justify-center -ml-1.5 border-2 border-white">+{{ $team->users->count()-5 }}</span>
                @endif
            </div>
            <a href="{{ route('teams.show', $team) }}" class="btn-secondary btn-sm">View</a>
        </div>
    </div>
    @endforeach
</div>
@else
<div class="card p-16 text-center">
    <i class="bi bi-people text-4xl text-slate-300 dark:text-slate-600"></i>
    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mt-4">No teams yet</h3>
    <p class="text-sm text-slate-500 mt-2 mb-6">Create a team to collaborate with others</p>
    @can('create', App\Models\Team::class)
    <a href="{{ route('teams.create') }}" class="btn-primary"><i class="bi bi-plus-lg"></i> Create Team</a>
    @endcan
</div>
@endif
@endsection
