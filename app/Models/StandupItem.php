<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StandupItem extends Model
{
    protected $fillable = ['standup_id', 'task_id', 'description', 'status', 'sort_order'];

    public function standup(): BelongsTo
    {
        return $this->belongsTo(Standup::class);
    }

    public static array $statuses = ['todo', 'in_progress', 'in_review', 'done'];
}
