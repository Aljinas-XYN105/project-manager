<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('project'));
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('is_public')) {
            $this->merge(['is_public' => false]);
        }
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
            'is_public'    => ['nullable', 'boolean'],
            'member_ids'   => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'exists:users,id'],
            'member_roles' => ['nullable', 'array'],
            'member_roles.*' => ['nullable', 'in:developer,manager,viewer'],
        ];
    }
}
