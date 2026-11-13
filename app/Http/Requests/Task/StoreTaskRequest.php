<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'               => ['required', 'string', 'max:255',
                Rule::unique('tasks', 'title')
                    ->where(fn ($q) => $q->where('project_id', $this->input('project_id'))->whereNull('deleted_at')),
            ],
            'description'         => ['nullable', 'string'],
            'project_id'          => ['required', 'exists:projects,id'],
            'column_id'           => ['required', 'exists:columns,id'],
            'parent_id'           => ['nullable', 'exists:tasks,id'],
            'type'                => ['nullable', 'in:task,bug,feature,story,epic'],
            'sprint_id'           => ['nullable', 'exists:sprints,id'],
            'status'              => ['nullable', 'in:todo,in_progress,qa_testing,in_review,done,cancelled'],
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

    public function messages(): array
    {
        return [
            'title.unique' => 'A task with this title already exists in this project.',
        ];
    }
}
