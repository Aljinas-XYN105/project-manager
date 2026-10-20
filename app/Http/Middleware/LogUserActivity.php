<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogUserActivity
{
    // Auth routes are logged by AuthActivitySubscriber; polling routes are noise.
    private const SKIP_ROUTES = [
        'login', 'logout', 'register', 'password.*', 'verification.*',
        'notifications.read', 'notifications.read-all', 'presence.ping',
    ];

    private const HIDDEN_INPUT = [
        '_token', '_method', 'password', 'password_confirmation', 'current_password', 'avatar', 'file',
    ];

    private const VERBS = [
        'store' => 'created', 'update' => 'updated', 'destroy' => 'deleted', 'move' => 'moved',
        'status' => 'changed status of', 'archive' => 'archived', 'restore' => 'restored',
        'start' => 'started', 'complete' => 'completed', 'stop' => 'stopped',
        'toggle-active' => 'toggled active status of', 'pin' => 'pinned', 'convert' => 'converted',
        'add' => 'added', 'remove' => 'removed', 'add-task' => 'added a task to',
        'remove-task' => 'removed a task from', 'bulk-update' => 'bulk-updated', 'reorder' => 'reordered',
        'clock-in' => 'clocked in', 'clock-out' => 'clocked out', 'availability' => 'changed availability',
        'reset-password' => 'reset password of',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        $lastIdBefore = (int) ActivityLog::max('id');
        $response = $next($request);

        $user = $request->user();
        if (! $user || $request->routeIs(...self::SKIP_ROUTES)) {
            return $response;
        }

        $meta = [
            'method'     => $request->method(),
            'url'        => $request->path(),
            'route'      => $request->route()?->getName(),
            'ip'         => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 180),
            'status'     => $response->getStatusCode(),
        ];

        // Logs written by services during this request get the request metadata attached.
        $existing = ActivityLog::where('id', '>', $lastIdBefore)
            ->where('causer_type', User::class)
            ->where('causer_id', $user->id)
            ->get();

        foreach ($existing as $log) {
            $log->update(['properties' => array_merge($log->properties ?? [], $meta)]);
        }

        [$description, $subject] = $this->describe($request);

        if ($existing->contains('description', $description)) {
            return $response;
        }

        ActivityLog::create([
            'log_name'     => 'audit',
            'description'  => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->getKey(),
            'causer_type'  => User::class,
            'causer_id'    => $user->id,
            'properties'   => array_merge($meta, ['input' => $this->sanitizedInput($request)]),
        ]);

        return $response;
    }

    private function describe(Request $request): array
    {
        $route = $request->route();
        $name  = $route?->getName();

        if (! $name) {
            return [strtolower($request->method()).' '.$request->path(), null];
        }

        $subject = collect($route->parameters())->last(fn ($p) => $p instanceof Model);
        $parts   = explode('.', $name);
        $action  = array_pop($parts);
        $verb    = self::VERBS[$action] ?? str_replace('-', ' ', $action);

        if (in_array($action, ['clock-in', 'clock-out'])) {
            return [$verb, null];
        }
        if ($action === 'availability') {
            $label = User::AVAILABILITY[$request->input('availability')]['label'] ?? $request->input('availability');
            return ["set status to {$label}", null];
        }

        if ($subject && $action !== 'store') {
            $noun = Str::of(class_basename($subject))->snake(' ')->lower();
            $extra = in_array($action, ['add', 'remove']) && count($parts) > 1 ? ' '.Str::singular(end($parts)).' on' : '';
            return [trim("{$verb}{$extra} {$noun} \"{$this->label($subject)}\""), $subject];
        }

        $noun  = Str::singular(str_replace('-', ' ', end($parts) ?: 'item'));
        $title = $request->input('title') ?? $request->input('name');
        $desc  = "{$verb} {$noun}".($title ? " \"".Str::limit($title, 80)."\"" : '');
        if (! $title && $request->filled('user_id') && ($target = User::withTrashed()->find($request->input('user_id')))) {
            $desc .= " for {$target->name}";
        }
        if ($subject) {
            $desc .= ' in '.Str::of(class_basename($subject))->snake(' ')->lower()." \"{$this->label($subject)}\"";
        }

        return [$desc, $subject];
    }

    private function label(Model $model): string
    {
        return Str::limit((string) ($model->title ?? $model->name ?? '#'.$model->getKey()), 80);
    }

    private function sanitizedInput(Request $request): array
    {
        return collect($request->except(self::HIDDEN_INPUT))
            ->take(30)
            ->map(fn ($v) => is_string($v) ? Str::limit($v, 200) : $v)
            ->all();
    }
}
