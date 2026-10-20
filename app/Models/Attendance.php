<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'user_id', 'work_date', 'clock_in_at', 'clock_out_at',
        'clock_in_ip', 'clock_out_ip', 'source', 'note', 'edited_by',
    ];

    protected function casts(): array
    {
        return [
            'work_date'    => 'date',
            'clock_in_at'  => 'datetime',
            'clock_out_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    public function scopeOpen($query)
    {
        return $query->whereNull('clock_out_at');
    }

    public function getDurationMinutesAttribute(): int
    {
        return (int) $this->clock_in_at->diffInMinutes($this->clock_out_at ?? now());
    }

    public function getDurationFormattedAttribute(): string
    {
        $m = $this->duration_minutes;
        return sprintf('%dh %02dm', intdiv($m, 60), $m % 60);
    }

    public static function localToday(User $user): string
    {
        return now($user->timezone ?: config('app.timezone'))->toDateString();
    }

    public static function clockIn(User $user, ?string $ip, string $source = 'login'): self
    {
        static::closeStale($user);

        return static::open()->where('user_id', $user->id)->whereDate('work_date', static::localToday($user))->first()
            ?? static::create([
                'user_id'     => $user->id,
                'work_date'   => static::localToday($user),
                'clock_in_at' => now(),
                'clock_in_ip' => $ip,
                'source'      => $source,
            ]);
    }

    public static function clockOut(User $user, ?string $ip): ?self
    {
        $open = static::open()->where('user_id', $user->id)->latest('clock_in_at')->first();
        $open?->update(['clock_out_at' => now(), 'clock_out_ip' => $ip]);
        return $open;
    }

    // Sessions that ended without a logout (closed browser, expired) are closed at the user's last activity.
    public static function closeStale(User $user): void
    {
        $tz = $user->timezone ?: config('app.timezone');

        static::open()->where('user_id', $user->id)->whereDate('work_date', '<', static::localToday($user))->get()
            ->each(function (self $a) use ($user, $tz) {
                $endOfShiftDay = $a->clock_in_at->copy()->setTimezone($tz)->endOfDay()->utc();
                $end = $user->last_seen_at && $user->last_seen_at->between($a->clock_in_at, $endOfShiftDay)
                    ? $user->last_seen_at
                    : $endOfShiftDay;
                $a->update(['clock_out_at' => $end, 'note' => trim(($a->note ?? '').' (auto-closed)')]);
            });
    }
}
