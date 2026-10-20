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
        'started_at', 'stopped_at', 'duration_seconds', 'is_billable', 'hourly_rate',
    ];

    protected function casts(): array
    {
        return [
            'started_at'  => 'datetime',
            'stopped_at'  => 'datetime',
            'is_billable' => 'boolean',
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
        return $query->whereNull('stopped_at');
    }

    // Accessors
    public function getDurationFormattedAttribute(): string
    {
        $seconds = $this->duration_seconds ?? 0;
        $hours   = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs    = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }

    public function getBilledAmountAttribute(): float
    {
        if (!$this->hourly_rate || !$this->duration_seconds) {
            return 0;
        }
        return round(($this->duration_seconds / 3600) * $this->hourly_rate, 2);
    }
}
