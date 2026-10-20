@extends('layouts.app')
@section('title', 'Org chart')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Org chart</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Built from each member's "Reports to" setting.</p>
    </div>
    <div class="tab-bar">
        <a href="{{ route('people.index') }}" class="tab-item"><i class="bi bi-grid-3x3-gap"></i> Directory</a>
        <a href="{{ route('people.org-chart') }}" class="tab-item active"><i class="bi bi-diagram-3"></i> Org chart</a>
    </div>
</div>

@if($byManager->except([0])->isEmpty())
<div class="alert-info mb-5">
    <i class="bi bi-info-circle-fill"></i>
    <span>No reporting lines are set yet.
        @if(auth()->user()->hasRole('admin') || auth()->user()->can('users.manage'))
        Set "Reports to" when <a href="{{ route('users.index') }}" class="underline">editing members</a>.
        @else
        Ask an admin to set "Reports to" for each member.
        @endif
    </span>
</div>
@endif

<div class="card p-6 overflow-x-auto">
    <ul class="space-y-2 min-w-max">
        @foreach($roots as $root)
            @include('people._org-node', ['person' => $root, 'depth' => 0, 'seen' => []])
        @endforeach
    </ul>
</div>
@endsection
