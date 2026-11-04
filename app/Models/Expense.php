<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    public const CATEGORIES = [
        'Travel', 'Meals', 'Office Supplies', 'Software', 'Training', 'Accommodation', 'Other',
    ];

    public const STATUSES = [
        'pending'  => ['label' => 'Pending',  'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'],
        'approved' => ['label' => 'Approved', 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'],
        'rejected' => ['label' => 'Rejected', 'class' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'],
    ];

    protected $fillable = [
        'user_id', 'title', 'amount', 'currency', 'category',
        'expense_date', 'description', 'receipt_path', 'receipt_name', 'receipts',
        'status', 'reviewed_by', 'reviewed_at', 'review_note',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount'       => 'decimal:2',
            'reviewed_at'  => 'datetime',
            'receipts'     => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function canBeReviewedBy(User $reviewer): bool
    {
        if ($reviewer->id === $this->user_id) {
            return false;
        }

        return $reviewer->hasRole('admin')
            || $reviewer->hasRole('manager')
            || $this->user?->manager_id === $reviewer->id;
    }

    public function getFormattedAmountAttribute(): string
    {
        return $this->currency.' '.number_format($this->amount, 2);
    }
}
