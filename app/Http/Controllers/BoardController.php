<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BoardController extends Controller
{
    /**
     * Display the board view for a project.
     */
    public function index(Request $request, $projectId)
    {
        $project = Project::where('project_id', $projectId)
            ->where('organization_id', $request->user()->organization_id)
            ->with('owner:id,name,avatar')
            ->firstOrFail();

        // Check if user has access to the project
        if (!$project->isMember($request->user())) {
            abort(403, 'You are not a member of this project.');
        }

        // Define status groups in order
        $statusGroups = ['Todo', 'Analysis', 'Ready', 'Progress', 'Review', 'QA', 'Completed'];

        // Get all tasks for this project grouped by status
        $tasks = Task::where('project_id', $project->id)
            ->with(['category:id,name,color', 'assignee:id,name,avatar', 'owner:id,name,avatar', 'tags:id,name,color'])
            ->get()
            ->groupBy('status');

        // Organize tasks by status in the correct order
        $tasksByStatus = [];
        foreach ($statusGroups as $status) {
            $tasksByStatus[$status] = $tasks->get($status, collect())->map(function ($task) {
                return [
                    'id' => $task->id,
                    'task_id' => $task->task_id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'status' => $task->status,
                    'estimation' => $task->estimation,
                    'attachments' => $task->attachments,
                    'category' => $task->category ? [
                        'id' => $task->category->id,
                        'name' => $task->category->name,
                        'color' => $task->category->color,
                    ] : null,
                    'assignee' => $task->assignee ? [
                        'id' => $task->assignee->id,
                        'name' => $task->assignee->name,
                        'avatar' => $task->assignee->avatar,
                    ] : null,
                    'owner' => $task->owner ? [
                        'id' => $task->owner->id,
                        'name' => $task->owner->name,
                        'avatar' => $task->owner->avatar,
                    ] : null,
                    'tags' => $task->tags->map(fn($tag) => [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'color' => $tag->color,
                    ]),
                ];
            })->values();
        }

        // Get all categories, tags, and users for the modal
        $categories = Category::where('project_id', $project->id)
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        $tags = Tag::where('project_id', $project->id)
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        $users = User::where('organization_id', $project->organization_id)
            ->select('id', 'name', 'avatar')
            ->orderBy('name')
            ->get();

        return Inertia::render('Projects/Board/Index', [
            'project' => $project,
            'tasksByStatus' => $tasksByStatus,
            'statusGroups' => $statusGroups,
            'categories' => $categories,
            'tags' => $tags,
            'users' => $users,
        ]);
    }

    /**
     * Update task status via drag and drop.
     */
    public function updateStatus(Request $request, $projectId, Task $task)
    {
        $project = Project::where('project_id', $projectId)
            ->where('organization_id', $request->user()->organization_id)
            ->firstOrFail();

        // Check if user has access to the project
        if (!$project->isMember($request->user())) {
            abort(403, 'You are not a member of this project.');
        }

        // Verify task belongs to this project
        if ($task->project_id !== $project->id) {
            abort(403, 'Task does not belong to this project.');
        }

        $validated = $request->validate([
            'status' => 'required|in:Todo,Analysis,Ready,Progress,Review,QA,Completed',
        ]);

        $task->update(['status' => $validated['status']]);

        return back()->with('success', 'Task status updated successfully.');
    }
}
