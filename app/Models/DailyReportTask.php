<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyReportTask extends Model
{
    protected $fillable = [
        'daily_report_id', 'task_id', 'task_description', 'hours_spent', 'notes',
        'status_update', 'is_blocked',
    ];

    protected function casts(): array
    {
        return [
            'hours_spent' => 'float',
            'is_blocked'  => 'boolean',
        ];
    }

    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(DailyReport::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
