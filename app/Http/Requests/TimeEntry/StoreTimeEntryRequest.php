<?php

namespace App\Http\Requests\TimeEntry;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimeEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'task_id'     => ['required', 'exists:tasks,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'started_at'  => ['required', 'date'],
            'stopped_at'  => ['nullable', 'date', 'after:started_at'],
            'is_billable' => ['nullable', 'boolean'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
