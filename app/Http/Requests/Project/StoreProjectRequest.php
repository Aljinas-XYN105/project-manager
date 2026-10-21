<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Project::class);
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'team_id'     => ['nullable', 'exists:teams,id'],
            'status'      => ['nullable', 'in:planning,active,on_hold,completed,archived'],
            'priority'    => ['nullable', 'in:low,medium,high,critical'],
            'color'       => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'start_date'  => ['nullable', 'date'],
            'due_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
            'budget'      => ['nullable', 'numeric', 'min:0'],
            'is_public'   => ['nullable', 'boolean'],
        ];
    }
}
