<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('task'));
    }

    public function rules(): array
    {
        return [
            'title'               => ['sometimes', 'required', 'string', 'max:255'],
            'description'         => ['nullable', 'string'],
            'column_id'           => ['sometimes', 'exists:columns,id'],
            'type'                => ['nullable', 'in:task,bug,feature,story,epic'],
            'sprint_id'           => ['nullable', 'exists:sprints,id'],
            'status'              => ['nullable', 'in:todo,in_progress,in_review,done,cancelled'],
            'priority'            => ['nullable', 'in:low,medium,high,critical'],
            'due_date'            => ['nullable', 'date'],
            'estimated_hours'     => ['nullable', 'numeric', 'min:0'],
            'story_points'        => ['nullable', 'integer', 'min:0', 'max:100'],
            'assignee_ids'        => ['nullable', 'array'],
            'assignee_ids.*'      => ['exists:users,id'],
            'label_ids'           => ['nullable', 'array'],
            'label_ids.*'         => ['exists:labels,id'],
            'severity'            => ['nullable', 'in:blocker,critical,major,minor,trivial'],
            'steps_to_reproduce'  => ['nullable', 'string'],
            'expected_behavior'   => ['nullable', 'string'],
            'actual_behavior'     => ['nullable', 'string'],
            'environment'         => ['nullable', 'string', 'max:255'],
        ];
    }
}
