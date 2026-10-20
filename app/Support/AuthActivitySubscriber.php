<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Str;

class AuthActivitySubscriber
{
    public function handleLogin(Login $event): void
    {
        $user = $event->user;
        if (! $user instanceof User) {
            return;
        }

        Attendance::clockIn($user, request()->ip());
        $user->forceFill(['availability' => 'available', 'last_seen_at' => now()])->saveQuietly();

        $this->log('logged in', $user, ['remember' => $event->remember]);
    }

    public function handleLogout(Logout $event): void
    {
        $user = $event->user;
        if (! $user instanceof User) {
            return;
        }

        Attendance::clockOut($user, request()->ip());
        $user->forceFill(['availability' => 'offline'])->saveQuietly();

        $this->log('logged out', $user);
    }

    public function handleFailed(Failed $event): void
    {
        ActivityLog::create([
            'log_name'     => 'auth',
            'description'  => 'failed login attempt',
            'causer_type'  => $event->user ? User::class : null,
            'causer_id'    => $event->user?->getAuthIdentifier(),
            'properties'   => $this->meta(['email' => $event->credentials['email'] ?? null]),
        ]);
    }

    public function handleRegistered(Registered $event): void
    {
        $this->log('registered an account', $event->user);
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        $this->log('reset their password', $event->user);
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class         => 'handleLogin',
            Logout::class        => 'handleLogout',
            Failed::class        => 'handleFailed',
            Registered::class    => 'handleRegistered',
            PasswordReset::class => 'handlePasswordReset',
        ];
    }

    private function log(string $description, $user, array $extra = []): void
    {
        ActivityLog::create([
            'log_name'     => 'auth',
            'description'  => $description,
            'subject_type' => User::class,
            'subject_id'   => $user->getAuthIdentifier(),
            'causer_type'  => User::class,
            'causer_id'    => $user->getAuthIdentifier(),
            'properties'   => $this->meta($extra),
        ]);
    }

    private function meta(array $extra = []): array
    {
        return array_merge([
            'ip'         => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 180),
        ], $extra);
    }
}
