<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimeEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'task_id', 'user_id', 'project_id', 'description',
        'status', 'started_at', 'stopped_at', 'paused_at',
        'duration_seconds', 'paused_seconds', 'is_billable', 'hourly_rate', 'auto_paused',
    ];

    protected function casts(): array
    {
        return [
            'started_at'  => 'datetime',
            'stopped_at'  => 'datetime',
            'paused_at'   => 'datetime',
            'is_billable' => 'boolean',
            'auto_paused' => 'boolean',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // Scopes
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeBillable($query)
    {
        return $query->where('is_billable', true);
    }

    public function scopeByDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('started_at', [$from, $to]);
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['running', 'paused']);
    }

    // Accessors
    public function getIsRunningAttribute(): bool
    {
        return $this->status === 'running';
    }

    public function getIsPausedAttribute(): bool
    {
        return $this->status === 'paused';
    }

    /** Elapsed seconds including any paused time already accumulated */
    public function getElapsedSecondsAttribute(): int
    {
        if ($this->status === 'stopped') {
            return $this->duration_seconds ?? 0;
        }
        $base = $this->started_at->diffInSeconds(now());
        return max(0, $base - ($this->paused_seconds ?? 0));
    }

    public function getDurationFormattedAttribute(): string
    {
        $seconds = $this->duration_seconds ?? 0;
        $hours   = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs    = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }

    public function getElapsedFormattedAttribute(): string
    {
        $s = $this->elapsed_seconds;
        return sprintf('%02d:%02d:%02d', floor($s / 3600), floor(($s % 3600) / 60), $s % 60);
    }

    public function getDurationHoursAttribute(): float
    {
        return round($this->elapsed_seconds / 3600, 4);
    }

    public function getBilledAmountAttribute(): float
    {
        if (!$this->hourly_rate || !$this->duration_seconds) {
            return 0;
        }
        return round(($this->duration_seconds / 3600) * $this->hourly_rate, 2);
    }
}
