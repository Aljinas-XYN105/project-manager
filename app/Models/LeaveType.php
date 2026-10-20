<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    public const COLORS = ['violet', 'rose', 'amber', 'emerald', 'blue', 'slate'];

    protected $fillable = ['name', 'color', 'days_per_year', 'is_paid', 'is_active'];

    protected function casts(): array
    {
        return [
            'days_per_year' => 'float',
            'is_paid'       => 'boolean',
            'is_active'     => 'boolean',
        ];
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function getIsUnlimitedAttribute(): bool
    {
        return $this->days_per_year <= 0;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
