<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    public function index($projectId)
    {
        $project = Project::where('project_id', $projectId)
            ->where('organization_id', auth()->user()->organization_id)
            ->with('owner:id,name,avatar')
            ->firstOrFail();

        // Check if user has access to the project
        if (!$project->isMember(auth()->user())) {
            abort(403, 'You are not a member of this project.');
        }

        // Get tasks grouped by category (exclude completed tasks)
        $tasks = Task::where('project_id', $project->id)
            ->where('status', '!=', 'Completed')
            ->with(['category', 'tags', 'owner:id,name,avatar', 'assignee:id,name,avatar'])
            ->get()
            ->groupBy(function($task) {
                return $task->category ? $task->category->name : 'Uncategorized';
            });

        // Get all categories for this project with task counts (exclude closed categories)
        $categories = Category::where('project_id', $project->id)
            ->where('status', 'open')
            ->withCount([
                'tasks as total_tasks',
                'tasks as completed_tasks' => function ($query) {
                    $query->where('status', 'Completed');
                }
            ])
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                $category->completion_percentage = $category->total_tasks > 0 
                    ? round(($category->completed_tasks / $category->total_tasks) * 100) 
                    : 0;
                return $category;
            });

        // Get all tags for this project
        $tags = Tag::where('project_id', $project->id)
            ->orderBy('name')
            ->get();

        // Get available users from the organization
        $users = User::where('organization_id', auth()->user()->organization_id)
            ->select('id', 'name', 'avatar')
            ->orderBy('name')
            ->get();

        return Inertia::render('Projects/Tasks/Index', [
            'project' => $project,
            'tasksGrouped' => $tasks,
            'categories' => $categories,
            'tags' => $tags,
            'users' => $users,
        ]);
    }

    public function store(Request $request, $projectId)
    {
        $project = Project::where('project_id', $projectId)
            ->where('organization_id', auth()->user()->organization_id)
            ->firstOrFail();

        // Check if user has access to the project
        if (!$project->isMember(auth()->user())) {
            abort(403, 'You are not a member of this project.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:Todo,Analysis,Ready,Progress,Review,QA,Completed',
            'category' => 'nullable|string|max:255',
            'assignee_id' => 'nullable|exists:users,id',
            'estimation' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240', // 10MB max per file
        ]);

        // Handle category - create new or use existing
        $categoryId = null;
        if ($request->filled('category')) {
            $category = Category::firstOrCreate([
                'project_id' => $project->id,
                'name' => $validated['category'],
            ], [
                'color' => $this->generateRandomColor(),
            ]);
            $categoryId = $category->id;
        }

        // Handle file uploads
        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('task-attachments', 'public');
                $attachmentPaths[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                ];
            }
        }

        // Convert estimation to hours
        $estimationHours = null;
        if ($request->filled('estimation')) {
            $estimationHours = $this->parseEstimation($validated['estimation']);
        }

        $task = Task::create([
            'project_id' => $project->id,
            'owner_id' => auth()->id(),
            'category_id' => $categoryId,
            'assignee_id' => $validated['assignee_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'estimation' => $estimationHours,
            'attachments' => !empty($attachmentPaths) ? $attachmentPaths : null,
        ]);

        // Handle tags - create new or attach existing
        if ($request->filled('tags')) {
            $tagIds = [];
            foreach ($request->tags as $tagName) {
                if (!empty($tagName)) {
                    $tag = Tag::firstOrCreate([
                        'project_id' => $project->id,
                        'name' => $tagName,
                    ], [
                        'color' => $this->generateRandomColor(),
                    ]);
                    $tagIds[] = $tag->id;
                }
            }
            $task->tags()->sync($tagIds);
        }

        return redirect()->back()->with('success', 'Task created successfully.');
    }

    public function update(Request $request, $projectId, Task $task)
    {
        $project = Project::where('project_id', $projectId)
            ->where('organization_id', auth()->user()->organization_id)
            ->firstOrFail();

        // Check if user has access to the project
        if (!$project->isMember(auth()->user())) {
            abort(403, 'You are not a member of this project.');
        }

        // Ensure task belongs to this project
        if ($task->project_id !== $project->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:Todo,Analysis,Ready,Progress,Review,QA,Completed',
            'category' => 'nullable|string|max:255',
            'assignee_id' => 'nullable|exists:users,id',
            'estimation' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
            'existing_attachments' => 'nullable|array',
        ]);

        // Handle category
        $categoryId = null;
        if ($request->filled('category')) {
            $category = Category::firstOrCreate([
                'project_id' => $project->id,
                'name' => $validated['category'],
            ], [
                'color' => $this->generateRandomColor(),
            ]);
            $categoryId = $category->id;
        }

        // Handle file uploads
        $existingAttachments = $request->input('existing_attachments', []);
        $attachmentPaths = $existingAttachments;

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('task-attachments', 'public');
                $attachmentPaths[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                ];
            }
        }

        // Convert estimation
        $estimationHours = null;
        if ($request->filled('estimation')) {
            $estimationHours = $this->parseEstimation($validated['estimation']);
        }

        $task->update([
            'category_id' => $categoryId,
            'assignee_id' => $validated['assignee_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'estimation' => $estimationHours,
            'attachments' => !empty($attachmentPaths) ? $attachmentPaths : null,
        ]);

        // Handle tags
        if ($request->has('tags')) {
            $tagIds = [];
            foreach ($request->tags ?? [] as $tagName) {
                if (!empty($tagName)) {
                    $tag = Tag::firstOrCreate([
                        'project_id' => $project->id,
                        'name' => $tagName,
                    ], [
                        'color' => $this->generateRandomColor(),
                    ]);
                    $tagIds[] = $tag->id;
                }
            }
            $task->tags()->sync($tagIds);
        }

        return redirect()->back()->with('success', 'Task updated successfully.');
    }

    public function destroy($projectId, Task $task)
    {
        $project = Project::where('project_id', $projectId)
            ->where('organization_id', auth()->user()->organization_id)
            ->firstOrFail();

        // Check if user has access to the project
        if (!$project->isMember(auth()->user())) {
            abort(403, 'You are not a member of this project.');
        }

        // Ensure task belongs to this project
        if ($task->project_id !== $project->id) {
            abort(403);
        }

        // Delete attachments
        if ($task->attachments) {
            foreach ($task->attachments as $attachment) {
                Storage::disk('public')->delete($attachment['path']);
            }
        }

        $task->delete();

        return redirect()->back()->with('success', 'Task deleted successfully.');
    }

    private function parseEstimation($estimation)
    {
        if (is_numeric($estimation)) {
            return (float) $estimation;
        }

        $estimation = strtolower(trim($estimation));

        // Handle formats like "2h", "30m", "2h 30m", "2.5h", etc.
        $hours = 0;
        $minutes = 0;

        // Match hours (e.g., "2h", "2.5h")
        if (preg_match('/(\d+(?:\.\d+)?)\s*h/', $estimation, $matches)) {
            $hours = (float) $matches[1];
        }

        // Match minutes (e.g., "30m")
        if (preg_match('/(\d+)\s*m/', $estimation, $matches)) {
            $minutes = (int) $matches[1];
        }

        // Convert to total hours
        return $hours + ($minutes / 60);
    }

    private function generateRandomColor()
    {
        $colors = [
            '#6366f1', // indigo
            '#8b5cf6', // violet
            '#ec4899', // pink
            '#f43f5e', // rose
            '#f97316', // orange
            '#eab308', // yellow
            '#84cc16', // lime
            '#10b981', // emerald
            '#14b8a6', // teal
            '#06b6d4', // cyan
            '#3b82f6', // blue
        ];

        return $colors[array_rand($colors)];
    }
}
