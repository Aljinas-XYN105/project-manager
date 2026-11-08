<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        return Inertia::render('Settings/Profile', [
            'user' => [
                'name'       => $user->name,
                'email'      => $user->email,
                'job_title'  => $user->job_title ?? '',
                'timezone'   => $user->timezone ?? 'UTC',
                'avatar_url' => $user->avatar_url,
                'role'       => ucfirst($user->roles->first()?->name ?? 'member'),
                'member_since' => $user->created_at->format('M Y'),
            ],
            'success'     => session('success'),
            'update_url'  => route('settings.profile.update'),
            'my_tasks_url'  => route('my-tasks'),
            'reports_url'   => route('daily-reports.index'),
            'time_url'      => route('time-entries.index'),
            'leave_url'     => route('leave.index'),
        ]);
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        } else {
            unset($data['avatar']);
        }

        // Handle password change
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        unset($data['current_password'], $data['password_confirmation'], $data['section']);

        $user->update($data);

        return back()->with('success', 'Profile updated.');
    }
}
