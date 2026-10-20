<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id', 'column_id', 'parent_id', 'created_by',
        'type', 'ticket_number', 'sprint_id',
        'title', 'description', 'status', 'priority', 'position',
        'story_points', 'estimated_hours', 'due_date',
        'started_at', 'completed_at', 'is_archived',
        'severity', 'steps_to_reproduce', 'expected_behavior', 'actual_behavior', 'environment',
    ];

    protected function casts(): array
    {
        return [
            'due_date'      => 'date',
            'started_at'    => 'datetime',
            'completed_at'  => 'datetime',
            'is_archived'   => 'boolean',
            'story_points'  => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(Column::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id');
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_assignees')
            ->withPivot('assigned_by')->withTimestamps();
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'task_labels');
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')
            ->whereNull('parent_id')->with('replies.user')->orderBy('created_at');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    public function dailyReportTasks(): HasMany
    {
        return $this->hasMany(DailyReportTask::class);
    }

    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'task_id', 'depends_on_id')
            ->withPivot('type');
    }

    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'depends_on_id', 'task_id')
            ->withPivot('type');
    }

    // Scopes
    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where(fn($q) => $q->where('created_by', $userId)
            ->orWhereHas('assignees', fn($a) => $a->where('users.id', $userId)));
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->whereNotIn('status', ['done', 'cancelled']);
    }

    public function scopeDueSoon($query, int $days = 3)
    {
        return $query->whereNotNull('due_date')
            ->whereBetween('due_date', [now(), now()->addDays($days)])
            ->whereNotIn('status', ['done', 'cancelled']);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(fn($q) => $q->where('title', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%"));
    }

    public function scopeRootTasks($query)
    {
        return $query->whereNull('parent_id');
    }

    // Accessors
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && !in_array($this->status, ['done', 'cancelled']);
    }

    public function getTotalTrackedSecondsAttribute(): int
    {
        return (int) $this->timeEntries()->sum('duration_seconds');
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'todo'        => 'secondary',
            'in_progress' => 'primary',
            'in_review'   => 'warning',
            'done'        => 'success',
            'cancelled'   => 'danger',
            default       => 'secondary',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'low'      => 'success',
            'medium'   => 'info',
            'high'     => 'warning',
            'critical' => 'danger',
            default    => 'secondary',
        };
    }

    public function getPriorityIconAttribute(): string
    {
        return match ($this->priority) {
            'low'      => 'bi-arrow-down-circle',
            'medium'   => 'bi-dash-circle',
            'high'     => 'bi-arrow-up-circle',
            'critical' => 'bi-exclamation-circle-fill',
            default    => 'bi-dash-circle',
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type ?? 'task') {
            'bug'     => 'bi-bug-fill',
            'feature' => 'bi-stars',
            'story'   => 'bi-bookmark-fill',
            'epic'    => 'bi-lightning-fill',
            default   => 'bi-check-square-fill',
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->type ?? 'task') {
            'bug'     => 'text-red-500',
            'feature' => 'text-purple-500',
            'story'   => 'text-blue-500',
            'epic'    => 'text-orange-500',
            default   => 'text-gray-400',
        };
    }

    public function getTicketKeyAttribute(): string
    {
        if (!$this->ticket_number) return "#{$this->id}";
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $this->project->slug ?? 'TASK'), 0, 6));
        return "{$prefix}-{$this->ticket_number}";
    }

    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'blocker'  => 'danger',
            'critical' => 'danger',
            'major'    => 'warning',
            'minor'    => 'info',
            'trivial'  => 'secondary',
            default    => 'secondary',
        };
    }
}
