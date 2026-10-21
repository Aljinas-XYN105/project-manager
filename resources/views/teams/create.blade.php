@extends('layouts.app')
@section('title', 'New Team')
@section('content')
<div class="max-w-lg mx-auto">
    <div class="page-header">
        <h1 class="page-title">New Team</h1>
        <a href="{{ route('teams.index') }}" class="btn-ghost"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
    <div class="card p-6">
        <form method="POST" action="{{ route('teams.store') }}" class="space-y-5">
            @csrf
            <div>
                <label class="form-label">Team Name <span class="text-rose-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required class="form-input" placeholder="e.g. Engineering">
                @error('name')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Description</label>
                <textarea name="description" rows="3" class="form-input resize-none" placeholder="What does this team work on?">{{ old('description') }}</textarea>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                <a href="{{ route('teams.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Create Team</button>
            </div>
        </form>
    </div>
</div>
@endsection
