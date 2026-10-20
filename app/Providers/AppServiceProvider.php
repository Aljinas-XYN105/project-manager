<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\TimeEntry;
use App\Policies\CommentPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TeamPolicy;
use App\Policies\TimeEntryPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        Project::class   => ProjectPolicy::class,
        Task::class      => TaskPolicy::class,
        Team::class      => TeamPolicy::class,
        TimeEntry::class => TimeEntryPolicy::class,
        Comment::class   => CommentPolicy::class,
    ];

    public function register(): void {}

    public function boot(): void
    {
        $this->registerPolicies();
        \Illuminate\Support\Facades\Event::subscribe(\App\Support\AuthActivitySubscriber::class);

        // Timestamps are stored in UTC; show them in the signed-in viewer's own timezone.
        \Illuminate\Support\Carbon::macro('inUserTz', function () {
            return $this->copy()->setTimezone(auth()->user()?->timezone ?: config('app.timezone'));
        });
    }

    protected function registerPolicies(): void
    {
        foreach ($this->policies as $model => $policy) {
            \Illuminate\Support\Facades\Gate::policy($model, $policy);
        }
    }
}
