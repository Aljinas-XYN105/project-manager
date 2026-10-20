@extends('layouts.app')

@section('title', 'Edit Team')

@section('content')
<div class="max-w-xl">
    <div class="page-header">
        <h1 class="page-title">Edit Team</h1>
        <a href="{{ route('teams.show', $team) }}" class="btn-secondary">Cancel</a>
    </div>

    <div class="card p-6">
        <form method="POST" action="{{ route('teams.update', $team) }}">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <div>
                    <label class="form-label">Team Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $team->name) }}" class="form-input" required autofocus>
                    @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3" class="form-input resize-none">{{ old('description', $team->settings['description'] ?? '') }}</textarea>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <a href="{{ route('teams.show', $team) }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
        </form>
    </div>

    {{-- Danger zone --}}
    @can('delete', $team)
    <div class="mt-6 card border-red-200 dark:border-red-900 p-5">
        <h3 class="font-semibold text-red-600 dark:text-red-400 mb-2">Danger Zone</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Deleting this team is permanent and cannot be undone. All projects associated with this team will be orphaned.</p>
        <form method="POST" action="{{ route('teams.destroy', $team) }}" onsubmit="return confirm('Delete team \'{{ addslashes($team->name) }}\'? This cannot be undone.')">
            @csrf @method('DELETE')
            <button type="submit" class="btn-danger btn-sm">Delete Team</button>
        </form>
    </div>
    @endcan
</div>
@endsection
