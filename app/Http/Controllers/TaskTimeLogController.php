<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskTimeLog;
use Illuminate\Http\Request;

class TaskTimeLogController extends Controller
{
    public function store(Request $request, $projectId, Task $task)
    {
        $project = Project::where('project_id', $projectId)
            ->where('organization_id', auth()->user()->organization_id)
            ->firstOrFail();

        if (!$project->isMember(auth()->user())) {
            abort(403);
        }

        if ($task->project_id !== $project->id) {
            abort(403);
        }

        $validated = $request->validate([
            'date'       => 'required|date',
            'time_spent' => 'required|integer|min:1|max:1440', // max 24h in minutes
            'comment'    => 'nullable|string|max:1000',
        ]);

        TaskTimeLog::create([
            'task_id'    => $task->id,
            'user_id'    => auth()->id(),
            'date'       => $validated['date'],
            'time_spent' => $validated['time_spent'],
            'comment'    => $validated['comment'] ?? null,
        ]);

        return redirect()->back();
    }

    public function update(Request $request, $projectId, Task $task, TaskTimeLog $timeLog)
    {
        $project = Project::where('project_id', $projectId)
            ->where('organization_id', auth()->user()->organization_id)
            ->firstOrFail();

        if (!$project->isMember(auth()->user())) {
            abort(403);
        }

        if ($task->project_id !== $project->id || $timeLog->task_id !== $task->id) {
            abort(403);
        }

        // Only the log owner can edit
        if ($timeLog->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'date'       => 'required|date',
            'time_spent' => 'required|integer|min:1|max:1440',
            'comment'    => 'nullable|string|max:1000',
        ]);

        $timeLog->update($validated);

        return redirect()->back();
    }

    public function destroy(Request $request, $projectId, Task $task, TaskTimeLog $timeLog)
    {
        $project = Project::where('project_id', $projectId)
            ->where('organization_id', auth()->user()->organization_id)
            ->firstOrFail();

        if (!$project->isMember(auth()->user())) {
            abort(403);
        }

        if ($task->project_id !== $project->id || $timeLog->task_id !== $task->id) {
            abort(403);
        }

        // Log owner or project owner can delete
        if ($timeLog->user_id !== auth()->id() && $project->user_id !== auth()->id()) {
            abort(403);
        }

        $timeLog->delete();

        return redirect()->back();
    }
}
