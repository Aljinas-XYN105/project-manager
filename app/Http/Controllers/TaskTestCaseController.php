<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskTestCase;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskTestCaseController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $data = $request->validate([
            'title'           => ['required', 'string', 'max:255',
                Rule::unique('task_test_cases', 'title')->where(fn ($q) => $q->where('task_id', $task->id)),
            ],
            'steps'           => ['nullable', 'string', 'max:5000'],
            'expected_result' => ['nullable', 'string', 'max:5000'],
        ], [
            'title.unique' => 'A test case with this title already exists for this task.',
        ]);

        $tc = $task->testCases()->create([
            ...$data,
            'created_by' => auth()->id(),
            'status'     => 'pending',
        ]);

        return back()->with('success', 'Test case added.');
    }

    public function update(Request $request, Task $task, TaskTestCase $testCase)
    {
        $this->authorize('update', $task);

        $data = $request->validate([
            'title'           => ['sometimes', 'required', 'string', 'max:255'],
            'steps'           => ['nullable', 'string', 'max:5000'],
            'expected_result' => ['nullable', 'string', 'max:5000'],
            'status'          => ['sometimes', 'in:pending,passed,failed'],
        ]);

        $testCase->update($data);

        return back()->with('success', 'Test case updated.');
    }

    public function destroy(Task $task, TaskTestCase $testCase)
    {
        $this->authorize('update', $task);

        $testCase->delete();

        return back()->with('success', 'Test case deleted.');
    }
}
