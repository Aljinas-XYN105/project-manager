<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    public const STATUSES = [
        'pending'   => ['label' => 'Pending',   'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'],
        'approved'  => ['label' => 'Approved',  'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'],
        'rejected'  => ['label' => 'Rejected',  'class' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'],
        'cancelled' => ['label' => 'Cancelled', 'class' => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'],
    ];

    protected $fillable = [
        'user_id', 'leave_type_id', 'start_date', 'end_date', 'is_half_day', 'days',
        'reason', 'status', 'reviewed_by', 'reviewed_at', 'review_note',
    ];

    protected function casts(): array
    {
        return [
            'start_date'  => 'date',
            'end_date'    => 'date',
            'is_half_day' => 'boolean',
            'days'        => 'float',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'approved']);
    }

    public function scopeOverlapping($query, $start, $end)
    {
        return $query->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start);
    }

    public function scopeOnDate($query, $date)
    {
        return $query->overlapping($date, $date);
    }

    /** Working days in the range: weekends and company holidays don't count. */
    public static function countWorkingDays(Carbon $start, Carbon $end, bool $halfDay = false): float
    {
        $holidays = Holiday::whereDate('date', '>=', $start->toDateString())->whereDate('date', '<=', $end->toDateString())
            ->pluck('date')->map(fn ($d) => $d->toDateString())->all();

        $days = collect(CarbonPeriod::create($start, $end))
            ->reject(fn (Carbon $d) => $d->isWeekend() || in_array($d->toDateString(), $holidays))
            ->count();

        return $halfDay && $days > 0 ? 0.5 : (float) $days;
    }

    public function canBeReviewedBy(User $reviewer): bool
    {
        if ($reviewer->id === $this->user_id) {
            return false;
        }

        return $reviewer->hasRole('admin')
            || $reviewer->can('leave.approve')
            || $this->user?->manager_id === $reviewer->id;
    }

    public function getDateRangeAttribute(): string
    {
        if ($this->start_date->equalTo($this->end_date)) {
            return $this->start_date->format('D, M j, Y').($this->is_half_day ? ' (half day)' : '');
        }

        $startFormat = $this->start_date->year === $this->end_date->year ? 'M j' : 'M j, Y';

        return $this->start_date->format($startFormat).' – '.$this->end_date->format('M j, Y');
    }
}
