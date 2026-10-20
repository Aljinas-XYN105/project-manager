<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyReport extends Model
{
    protected $fillable = [
        'user_id', 'report_date', 'summary',
        'plan_for_tomorrow', 'blockers', 'total_hours',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'total_hours' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reportTasks(): HasMany
    {
        return $this->hasMany(DailyReportTask::class);
    }
}
