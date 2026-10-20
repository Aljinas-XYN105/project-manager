<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id', 'owner_id', 'name', 'slug', 'description',
        'status', 'priority', 'color', 'start_date', 'due_date',
        'completed_at', 'budget', 'is_public', 'settings', 'ticket_counter',
    ];

    protected function casts(): array
    {
        return [
            'start_date'   => 'date',
            'due_date'     => 'date',
            'completed_at' => 'datetime',
            'settings'     => 'array',
            'is_public'    => 'boolean',
            'budget'       => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Project $project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->name);
            }
        });
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function columns(): HasMany
    {
        return $this->hasMany(Column::class)->orderBy('position');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function labels(): HasMany
    {
        return $this->hasMany(Label::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot('role')->withTimestamps();
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ProjectNote::class);
    }

    public function sprints(): HasMany
    {
        return $this->hasMany(Sprint::class)->orderByDesc('created_at');
    }

    public function activeSprint(): ?Sprint
    {
        return $this->sprints()->where('status', 'active')->first();
    }

    public function nextTicketNumber(): int
    {
        $this->increment('ticket_counter');
        return $this->fresh()->ticket_counter;
    }

    public function getKeyPrefixAttribute(): string
    {
        return strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $this->slug), 0, 6));
    }

    // Scopes
    public function scopeForUser($query, int $userId)
    {
        return $query->where('owner_id', $userId)
            ->orWhereHas('members', fn($q) => $q->where('users.id', $userId));
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(fn($q) => $q->where('name', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%"));
    }

    // Accessors
    public function getProgressPercentageAttribute(): int
    {
        $total = $this->tasks()->whereNull('parent_id')->count();
        if ($total === 0) {
            return 0;
        }
        $done = $this->tasks()->whereNull('parent_id')->where('status', 'done')->count();
        return (int) round(($done / $total) * 100);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && $this->status !== 'completed'
            && $this->status !== 'archived';
    }

    public function getTotalTrackedHoursAttribute(): float
    {
        return round($this->timeEntries()->sum('duration_seconds') / 3600, 2);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'planning'  => 'secondary',
            'active'    => 'success',
            'on_hold'   => 'warning',
            'completed' => 'primary',
            'archived'  => 'dark',
            default     => 'secondary',
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
}
