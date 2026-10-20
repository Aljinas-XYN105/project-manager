<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Column extends Model
{
    protected $fillable = ['project_id', 'name', 'slug', 'color', 'position', 'wip_limit', 'is_default', 'is_done'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean', 'is_done' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (Column $column) {
            if (empty($column->slug)) {
                $column->slug = Str::slug($column->name);
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('position');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }
}
