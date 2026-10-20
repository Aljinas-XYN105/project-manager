<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('project'));
    }

    public function rules(): array
    {
        return [
            'name'        => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status'      => ['nullable', 'in:planning,active,on_hold,completed,archived'],
            'priority'    => ['nullable', 'in:low,medium,high,critical'],
            'color'       => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'start_date'  => ['nullable', 'date'],
            'due_date'    => ['nullable', 'date'],
            'budget'      => ['nullable', 'numeric', 'min:0'],
            'is_public'   => ['nullable', 'boolean'],
        ];
    }
}
