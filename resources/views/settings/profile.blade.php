@extends('layouts.app')

@section('title', 'Profile Settings')

@section('content')
<div class="max-w-2xl space-y-6">
    <div class="page-header">
        <h1 class="page-title">Profile Settings</h1>
    </div>

    @if(session('success'))
    <div class="rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-4 py-3 text-sm text-green-700 dark:text-green-400">
        {{ session('success') }}
    </div>
    @endif

    {{-- Profile Info --}}
    <div class="card p-6">
        <h2 class="font-semibold text-slate-900 dark:text-white mb-5">Personal Information</h2>
        <form method="POST" action="{{ route('settings.profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            {{-- Avatar --}}
            <div class="flex items-center gap-5 mb-6">
                <img src="{{ auth()->user()->avatar_url }}" id="avatar-preview" class="w-16 h-16 rounded-2xl object-cover" alt="">
                <div>
                    <label class="btn btn-secondary btn-sm cursor-pointer">
                        Change Avatar
                        <input type="file" name="avatar" id="avatar-input" class="hidden" accept="image/*"
                            onchange="document.getElementById('avatar-preview').src = URL.createObjectURL(this.files[0])">
                    </label>
                    <p class="text-xs text-slate-500 mt-1">JPG, PNG up to 2MB</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" class="form-input" required>
                    @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" class="form-input" required>
                    @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Job Title</label>
                    <input type="text" name="job_title" value="{{ old('job_title', auth()->user()->job_title) }}" class="form-input" placeholder="e.g. Senior Developer">
                </div>
                <div>
                    <label class="form-label">Timezone</label>
                    <select name="timezone" class="form-select">
                        @foreach(timezone_identifiers_list() as $tz)
                        <option value="{{ $tz }}" @selected(auth()->user()->timezone == $tz)>{{ $tz }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
        </form>
    </div>

    {{-- Change Password --}}
    <div class="card p-6">
        <h2 class="font-semibold text-slate-900 dark:text-white mb-5">Change Password</h2>
        <form method="POST" action="{{ route('settings.profile.update') }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="section" value="password">

            <div class="space-y-4">
                <div>
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-input" autocomplete="current-password">
                    @error('current_password') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-input" autocomplete="new-password">
                    @error('password') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="password_confirmation" class="form-input" autocomplete="new-password">
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <button type="submit" class="btn-primary">Update Password</button>
            </div>
        </form>
    </div>

    {{-- Theme preference --}}
    <div class="card p-6">
        <h2 class="font-semibold text-slate-900 dark:text-white mb-2">Appearance</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Dark mode is controlled by the toggle in the navigation bar and stored locally in your browser.</p>
        <div x-data class="flex items-center gap-3">
            <span class="text-sm text-slate-700 dark:text-slate-300">Dark Mode</span>
            <button @click="$root.closest('[x-data]') || document.documentElement.dispatchEvent(new CustomEvent('toggle-dark'))"
                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                    :class="document.documentElement.classList.contains('dark') ? 'bg-violet-600' : 'bg-slate-200'">
                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                      :class="document.documentElement.classList.contains('dark') ? 'translate-x-6' : 'translate-x-1'"></span>
            </button>
        </div>
    </div>
</div>
@endsection
