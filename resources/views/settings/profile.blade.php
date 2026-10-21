@extends('layouts.app')

@section('title', 'Profile Settings')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title flex items-center gap-2.5">
            <span class="w-8 h-8 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                <i class="bi bi-person-fill text-violet-600 dark:text-violet-400 text-sm"></i>
            </span>
            Profile Settings
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Manage your account information and preferences</p>
    </div>
</div>

@if(session('success'))
<div class="mb-5 flex items-center gap-3 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-sm text-emerald-700 dark:text-emerald-400">
    <i class="bi bi-check-circle-fill flex-shrink-0"></i>
    {{ session('success') }}
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- Left column: main forms --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Profile Info --}}
        <div class="card p-6">
            <h2 class="font-semibold text-slate-900 dark:text-white mb-5 flex items-center gap-2">
                <i class="bi bi-person-circle text-slate-400"></i> Personal Information
            </h2>
            <form method="POST" action="{{ route('settings.profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                {{-- Avatar --}}
                <div class="flex items-center gap-5 mb-6 pb-6 border-b border-slate-100 dark:border-slate-700">
                    <img src="{{ auth()->user()->avatar_url }}" id="avatar-preview"
                         class="w-16 h-16 rounded-2xl object-cover ring-2 ring-slate-200 dark:ring-slate-700" alt="">
                    <div>
                        <label class="btn btn-secondary btn-sm cursor-pointer">
                            <i class="bi bi-upload"></i> Change Avatar
                            <input type="file" name="avatar" id="avatar-input" class="hidden" accept="image/*"
                                onchange="document.getElementById('avatar-preview').src = URL.createObjectURL(this.files[0])">
                        </label>
                        <p class="text-xs text-slate-400 mt-1.5">JPG or PNG, max 2 MB</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Full Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" class="form-input" required>
                        @error('name')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">Email <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" class="form-input" required>
                        @error('email')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">Job Title</label>
                        <input type="text" name="job_title" value="{{ old('job_title', auth()->user()->job_title) }}"
                               class="form-input" placeholder="e.g. Senior Developer">
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

                <div class="mt-5 flex justify-end">
                    <button type="submit" class="btn-primary">
                        <i class="bi bi-check-lg"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>

        {{-- Change Password --}}
        <div class="card p-6">
            <h2 class="font-semibold text-slate-900 dark:text-white mb-5 flex items-center gap-2">
                <i class="bi bi-shield-lock text-slate-400"></i> Change Password
            </h2>
            <form method="POST" action="{{ route('settings.profile.update') }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="section" value="password">

                <div class="space-y-4">
                    <div>
                        <label class="form-label">Current Password <span class="text-rose-500">*</span></label>
                        <input type="password" name="current_password" class="form-input" autocomplete="current-password">
                        @error('current_password')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">New Password <span class="text-rose-500">*</span></label>
                            <input type="password" name="password" class="form-input" autocomplete="new-password">
                            @error('password')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="form-label">Confirm New Password <span class="text-rose-500">*</span></label>
                            <input type="password" name="password_confirmation" class="form-input" autocomplete="new-password">
                        </div>
                    </div>
                    <p class="text-xs text-slate-400">Use at least 8 characters with a mix of letters and numbers.</p>
                </div>

                <div class="mt-5 flex justify-end">
                    <button type="submit" class="btn-primary">
                        <i class="bi bi-shield-check"></i> Update Password
                    </button>
                </div>
            </form>
        </div>

    </div>

    {{-- Right column: account info + appearance --}}
    <div class="space-y-5">

        {{-- Account card --}}
        <div class="card p-5">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4 text-sm flex items-center gap-2">
                <i class="bi bi-info-circle text-slate-400"></i> Account Info
            </h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between items-center">
                    <span class="text-slate-500 dark:text-slate-400">Member since</span>
                    <span class="font-medium text-slate-800 dark:text-slate-200">{{ auth()->user()->created_at->format('M Y') }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500 dark:text-slate-400">Role</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-400">
                        {{ ucfirst(auth()->user()->roles->first()?->name ?? 'member') }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500 dark:text-slate-400">Status</span>
                    <span class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Active
                    </span>
                </div>
            </div>
        </div>

        {{-- Appearance --}}
        <div class="card p-5">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4 text-sm flex items-center gap-2">
                <i class="bi bi-palette text-slate-400"></i> Appearance
            </h3>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Dark Mode</p>
                    <p class="text-xs text-slate-400 mt-0.5">Stored in your browser</p>
                </div>
                <button @click="darkMode = !darkMode"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors flex-shrink-0"
                        :class="darkMode ? 'bg-violet-600' : 'bg-slate-200 dark:bg-slate-700'">
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                          :class="darkMode ? 'translate-x-6' : 'translate-x-1'"></span>
                </button>
            </div>
        </div>

        {{-- Quick links --}}
        <div class="card p-5">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-3 text-sm flex items-center gap-2">
                <i class="bi bi-lightning text-slate-400"></i> Quick Links
            </h3>
            <div class="space-y-1">
                <a href="{{ route('my-tasks') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                    <i class="bi bi-check-square text-slate-400 text-xs"></i> My Tasks
                </a>
                <a href="{{ route('daily-reports.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                    <i class="bi bi-journal-text text-slate-400 text-xs"></i> My Reports
                </a>
                <a href="{{ route('time-entries.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                    <i class="bi bi-clock text-slate-400 text-xs"></i> Time Tracking
                </a>
                <a href="{{ route('leave.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                    <i class="bi bi-calendar2-check text-slate-400 text-xs"></i> Leave Balance
                </a>
            </div>
        </div>

    </div>
</div>
@endsection
