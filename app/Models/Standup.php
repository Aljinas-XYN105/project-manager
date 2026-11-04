<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Standup extends Model
{
    protected $fillable = ['user_id', 'standup_date', 'blockers'];

    protected $casts = ['standup_date' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StandupItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function itemsByStatus(string $status): \Illuminate\Database\Eloquent\Collection
    {
        return $this->items->where('status', $status)->values();
    }
}
