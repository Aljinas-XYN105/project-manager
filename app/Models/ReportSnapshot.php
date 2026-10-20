<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSnapshot extends Model
{
    protected $fillable = ['user_id', 'name', 'type', 'filters', 'result_data', 'generated_at'];

    protected function casts(): array
    {
        return [
            'filters'      => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
