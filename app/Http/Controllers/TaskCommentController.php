<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\Request;

class TaskCommentController extends Controller
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
            'body'      => 'required|string|max:5000',
            'parent_id' => 'nullable|exists:task_comments,id',
        ]);

        TaskComment::create([
            'task_id'   => $task->id,
            'user_id'   => auth()->id(),
            'parent_id' => $validated['parent_id'] ?? null,
            'body'      => $validated['body'],
        ]);

        return redirect()->back();
    }

    public function destroy(Request $request, $projectId, Task $task, TaskComment $comment)
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

        // Only comment owner or project owner can delete
        if ($comment->user_id !== auth()->id() && $project->owner_id !== auth()->id()) {
            abort(403);
        }

        $comment->delete();

        return redirect()->back();
    }
}
