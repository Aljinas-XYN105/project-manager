<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Conversation extends Model
{
    protected $fillable = ['type', 'name', 'conversable_type', 'conversable_id'];

    public function conversable(): MorphTo
    {
        return $this->morphTo();
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function latestMessage(): HasMany
    {
        return $this->hasMany(Message::class)->latest()->limit(1);
    }

    public function unreadCountFor(User $user): int
    {
        $pivot = $this->participants()->where('users.id', $user->id)->first()?->pivot;
        $since = $pivot?->last_read_at;

        return $this->messages()
            ->where('user_id', '!=', $user->id)
            ->when($since, fn ($q) => $q->where('created_at', '>', $since))
            ->count();
    }

    // Find or create a DM conversation between two users
    public static function directBetween(User $a, User $b): self
    {
        $existing = self::where('type', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('users.id', $a->id))
            ->whereHas('participants', fn ($q) => $q->where('users.id', $b->id))
            ->first();

        if ($existing) {
            return $existing;
        }

        $conv = self::create(['type' => 'direct']);
        $conv->participants()->attach([$a->id, $b->id]);

        return $conv;
    }
}
