<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    public const CATEGORIES = [
        'general' => ['label' => 'General', 'icon' => 'bi-megaphone-fill',   'color' => 'violet'],
        'hr'      => ['label' => 'HR',      'icon' => 'bi-people-fill',      'color' => 'blue'],
        'event'   => ['label' => 'Event',   'icon' => 'bi-calendar-event',   'color' => 'emerald'],
        'urgent'  => ['label' => 'Urgent',  'icon' => 'bi-exclamation-triangle-fill', 'color' => 'rose'],
        'policy'  => ['label' => 'Policy',  'icon' => 'bi-file-earmark-text', 'color' => 'amber'],
    ];

    protected $fillable = ['user_id', 'title', 'body', 'category', 'is_pinned', 'expires_on'];

    protected function casts(): array
    {
        return [
            'is_pinned'  => 'boolean',
            'expires_on' => 'date',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeVisible($query)
    {
        return $query->where(fn ($q) => $q->whereNull('expires_on')->orWhereDate('expires_on', '>=', today()))
            ->orderByDesc('is_pinned')
            ->latest();
    }

    public function getStyleAttribute(): array
    {
        return self::CATEGORIES[$this->category] ?? self::CATEGORIES['general'];
    }
}
