@extends('layouts.app')
@section('title', $user->exists ? 'Edit '.$user->name : 'Add Member')

@section('content')
@php
    $isSelf = $user->exists && $user->is(auth()->user());
    $currentRoles = old('roles', $user->exists ? $user->roles->pluck('name')->all() : ['member']);
@endphp
<div class="max-w-2xl mx-auto">
    <div class="page-header">
        <div>
            <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
                <a href="{{ route('users.index') }}" class="hover:text-violet-600">Members</a>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span>{{ $user->exists ? $user->name : 'New' }}</span>
            </div>
            <h1 class="page-title">{{ $user->exists ? 'Edit member' : 'Add member' }}</h1>
        </div>
        <a href="{{ route('users.index') }}" class="btn-ghost"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="space-y-5">
        @csrf
        @if($user->exists) @method('PUT') @endif

        <div class="card p-6 space-y-4">
            <h2 class="font-semibold text-slate-900 dark:text-white">Account</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Full name *</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="form-input" placeholder="Jane Smith" autofocus>
                    @error('name')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="form-input" placeholder="jane@company.com">
                    @error('email')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Job title</label>
                    <input type="text" name="job_title" value="{{ old('job_title', $user->job_title) }}" class="form-input" placeholder="e.g. Backend Developer">
                </div>
                <div>
                    <label class="form-label">Primary team</label>
                    <select name="team_id" class="form-select">
                        <option value="">No team</option>
                        @foreach($teams as $team)
                        <option value="{{ $team->id }}" @selected(old('team_id', $user->team_id) == $team->id)>{{ $team->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Timezone</label>
                    <select name="timezone" class="form-select">
                        @foreach(timezone_identifiers_list() as $tz)
                        <option value="{{ $tz }}" @selected(old('timezone', $user->timezone) === $tz)>{{ $tz }}</option>
                        @endforeach
                    </select>
                    <p class="form-hint">Used to show this member's attendance times.</p>
                </div>
                @unless($user->exists)
                <div>
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" autocomplete="new-password" placeholder="Leave empty to generate one">
                    @error('password')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                @endunless
            </div>
            <label class="flex items-center gap-3 pt-1 {{ $isSelf ? 'opacity-60' : 'cursor-pointer' }}">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true)) @disabled($isSelf)
                       class="rounded text-violet-600 border-slate-300 focus:ring-violet-500">
                <span class="text-sm text-slate-700 dark:text-slate-300">Account is active <span class="text-slate-400">— inactive members can't sign in</span></span>
            </label>
        </div>

        <div class="card p-6 space-y-4">
            <div>
                <h2 class="font-semibold text-slate-900 dark:text-white">Employment</h2>
                <p class="text-xs text-slate-500">Used for the people directory, org chart and leave approvals.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Employee ID</label>
                    <input type="text" name="employee_code" value="{{ old('employee_code', $user->employee_code) }}" class="form-input" placeholder="e.g. EMP-0042">
                    @error('employee_code')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Department</label>
                    <input type="text" name="department" value="{{ old('department', $user->department) }}" list="departments" class="form-input" placeholder="e.g. Engineering">
                    <datalist id="departments">
                        @foreach($departments as $d)<option value="{{ $d }}">@endforeach
                    </datalist>
                </div>
                <div>
                    <label class="form-label">Reports to</label>
                    <select name="manager_id" class="form-select">
                        <option value="">— No manager —</option>
                        @foreach($managers as $m)
                        <option value="{{ $m->id }}" @selected(old('manager_id', $user->manager_id) == $m->id)>{{ $m->name }}{{ $m->job_title ? ' · '.$m->job_title : '' }}</option>
                        @endforeach
                    </select>
                    <p class="form-hint">The manager can approve this person's leave.</p>
                    @error('manager_id')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Employment type</label>
                    <select name="employment_type" class="form-select">
                        @foreach(\App\Models\User::EMPLOYMENT_TYPES as $k => $label)
                        <option value="{{ $k }}" @selected(old('employment_type', $user->employment_type ?? 'full_time') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Joining date</label>
                    <input type="date" name="joined_on" value="{{ old('joined_on', $user->joined_on?->toDateString()) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Date of birth</label>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $user->date_of_birth?->toDateString()) }}" class="form-input">
                    @error('date_of_birth')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Phone</label>
                    <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" class="form-input" placeholder="+1 555 0100">
                </div>
                <div></div>
                <div>
                    <label class="form-label">Emergency contact name</label>
                    <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $user->emergency_contact_name) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Emergency contact phone</label>
                    <input type="tel" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $user->emergency_contact_phone) }}" class="form-input">
                </div>
            </div>
        </div>

        <div class="card p-6">
            <h2 class="font-semibold text-slate-900 dark:text-white">Roles *</h2>
            <p class="text-xs text-slate-500 mb-4">A member gets every permission from each role they have. <a href="{{ route('admin.roles.index') }}" class="text-violet-600 hover:underline">Manage roles</a></p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach($roles as $role)
                @php $lock = $isSelf && $role->name === 'admin'; @endphp
                <label class="flex items-center gap-3 px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer transition-colors
                              hover:border-violet-400 has-[:checked]:border-violet-500 has-[:checked]:bg-violet-50 dark:has-[:checked]:bg-violet-900/20">
                    <input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked(in_array($role->name, $currentRoles))
                           @if($lock) onclick="return false" @endif
                           class="rounded text-violet-600 border-slate-300 focus:ring-violet-500">
                    <span class="flex-1 text-sm text-slate-800 dark:text-slate-200">{{ Str::headline($role->name) }}</span>
                    <span class="text-[11px] text-slate-400">{{ $role->users_count }}</span>
                    @if($lock)<i class="bi bi-lock-fill text-xs text-slate-400" title="You can't remove your own admin role"></i>@endif
                </label>
                @endforeach
            </div>
            @error('roles')<p class="form-error mt-2">{{ $message }}</p>@enderror
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('users.index') }}" class="btn-ghost">Cancel</a>
            <button class="btn-primary"><i class="bi bi-check-lg"></i> {{ $user->exists ? 'Save changes' : 'Create member' }}</button>
        </div>
    </form>

    @if($user->exists && ! $isSelf)
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mt-8">
        <form method="POST" action="{{ route('users.reset-password', $user) }}" class="card p-5 space-y-3"
              onsubmit="return confirm('Reset the password for {{ $user->name }}?')">
            @csrf
            <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2"><i class="bi bi-key text-amber-500"></i> Reset password</h3>
            <input type="password" name="password" class="form-input text-sm" autocomplete="new-password" placeholder="New password (empty = generate)">
            <button class="btn-secondary btn-sm w-full justify-center">Reset password</button>
        </form>
        <form method="POST" action="{{ route('users.destroy', $user) }}" class="card p-5 space-y-3 border-rose-200 dark:border-rose-900"
              onsubmit="return confirm('Remove {{ $user->name }}? They will no longer be able to sign in. Their tasks and history are kept.')">
            @csrf @method('DELETE')
            <h3 class="font-semibold text-rose-600 dark:text-rose-400 flex items-center gap-2"><i class="bi bi-person-x"></i> Remove member</h3>
            <p class="text-xs text-slate-500">Signs them out and hides them from the team. Tasks, reports and logs are kept.</p>
            <button class="btn-danger btn-sm w-full justify-center">Remove {{ $user->name }}</button>
        </form>
    </div>
    @endif
</div>
@endsection
