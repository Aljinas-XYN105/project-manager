<?php

namespace App\Http\Controllers;

use App\Models\Column;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ColumnController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $request->validate([
            'name'      => 'required|string|max:80',
            'color'     => 'nullable|string|max:20',
            'wip_limit' => 'nullable|integer|min:1|max:100',
        ]);

        $position = $project->columns()->max('position') + 1;

        $column = $project->columns()->create([
            'name'      => $request->name,
            'slug'      => Str::slug($request->name),
            'color'     => $request->input('color', '#6B7280'),
            'wip_limit' => $request->wip_limit,
            'position'  => $position,
        ]);

        return response()->json(['column' => $column]);
    }

    public function update(Request $request, Column $column)
    {
        $this->authorize('update', $column->project);

        $request->validate([
            'name'      => 'sometimes|required|string|max:80',
            'color'     => 'nullable|string|max:20',
            'wip_limit' => 'nullable|integer|min:1|max:100',
        ]);

        $column->update($request->only(['name', 'color', 'wip_limit']));

        return response()->json(['column' => $column->fresh()]);
    }

    public function destroy(Column $column)
    {
        $this->authorize('update', $column->project);

        // Move tasks to the first remaining column
        $fallback = $column->project->columns()
            ->where('id', '!=', $column->id)
            ->orderBy('position')
            ->first();

        if ($fallback) {
            Task::where('column_id', $column->id)->update(['column_id' => $fallback->id]);
        }

        $column->delete();

        return response()->json(['success' => true]);
    }

    public function reorder(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $request->validate(['order' => 'required|array']);

        DB::transaction(function () use ($request) {
            foreach ($request->order as $position => $columnId) {
                Column::where('id', $columnId)->update(['position' => $position]);
            }
        });

        return response()->json(['success' => true]);
    }
}
