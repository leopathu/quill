<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\Category;
use App\Models\TaskTimeLog;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

class ProjectViewController extends Controller
{
    /**
     * Display the project dashboard.
     */
    public function show(string $projectId): Response
    {
        $project = Project::where('project_id', $projectId)
            ->where('organization_id', auth()->user()->organization_id)
            ->with('owner:id,name,avatar')
            ->firstOrFail();

        if (!$project->isMember(auth()->user())) {
            abort(403);
        }

        // ── Task stats ────────────────────────────────────────────────────
        $allTasks = Task::where('project_id', $project->id)->withTrashed(false)->get();
        $totalTasks     = $allTasks->count();
        $completedTasks = $allTasks->where('status', 'Completed')->count();
        $todoTasks      = $allTasks->where('status', 'Todo')->count();
        $inProgressTasks = $allTasks->where('status', 'In Progress')->count();
        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        // ── Tasks by status ───────────────────────────────────────────────
        $tasksByStatus = $allTasks->groupBy('status')->map->count()->toArray();

        // ── Tasks by category ─────────────────────────────────────────────
        $categories = Category::where('project_id', $project->id)->get(['id', 'name', 'color']);
        $tasksByCategory = $categories->map(function ($cat) use ($project) {
            $tasks = Task::where('project_id', $project->id)
                ->where('category_id', $cat->id)
                ->get();
            return [
                'name'      => $cat->name,
                'color'     => $cat->color,
                'total'     => $tasks->count(),
                'completed' => $tasks->where('status', 'Completed')->count(),
                'todo'      => $tasks->where('status', 'Todo')->count(),
                'in_progress' => $tasks->where('status', 'In Progress')->count(),
            ];
        })->values()->toArray();

        // Uncategorised tasks
        $uncatTasks = Task::where('project_id', $project->id)->whereNull('category_id')->get();
        if ($uncatTasks->count() > 0) {
            $tasksByCategory[] = [
                'name'        => 'Uncategorised',
                'color'       => '#6b7280',
                'total'       => $uncatTasks->count(),
                'completed'   => $uncatTasks->where('status', 'Completed')->count(),
                'todo'        => $uncatTasks->where('status', 'Todo')->count(),
                'in_progress' => $uncatTasks->where('status', 'In Progress')->count(),
            ];
        }

        // ── Team members ──────────────────────────────────────────────────
        $teamMembers = $project->team()
            ->withPivot('role')
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => [
                'id'     => $u->id,
                'name'   => $u->name,
                'avatar' => $u->avatar,
                'role'   => $u->pivot->role,
                'tasks_assigned' => Task::where('project_id', $project->id)
                    ->where('assignee_id', $u->id)->count(),
                'tasks_completed' => Task::where('project_id', $project->id)
                    ->where('assignee_id', $u->id)
                    ->where('status', 'Completed')->count(),
            ]);

        // ── Time logged ───────────────────────────────────────────────────
        $totalMinutes = TaskTimeLog::whereHas('task', fn ($q) => $q->where('project_id', $project->id))
            ->sum('time_spent');

        // ── Recent tasks ──────────────────────────────────────────────────
        $recentTasks = Task::where('project_id', $project->id)
            ->with('assignee:id,name,avatar', 'category:id,name,color')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($t) => [
                'id'       => $t->id,
                'title'    => $t->title,
                'status'   => $t->status,
                'assignee' => $t->assignee,
                'category' => $t->category,
                'created_at' => $t->created_at->diffForHumans(),
            ]);

        return Inertia::render('Projects/View', [
            'project' => [
                'id'          => $project->id,
                'name'        => $project->name,
                'project_id'  => $project->project_id,
                'description' => $project->description,
                'status'      => $project->status,
                'logo'        => $project->logo,
                'owner'       => [
                    'name'   => $project->owner->name,
                    'avatar' => $project->owner->avatar,
                ],
                'created_at' => $project->created_at->format('M d, Y'),
            ],
            'stats' => [
                'total_tasks'      => $totalTasks,
                'completed_tasks'  => $completedTasks,
                'todo_tasks'       => $todoTasks,
                'in_progress_tasks'=> $inProgressTasks,
                'completion_rate'  => $completionRate,
                'team_count'       => $teamMembers->count(),
                'total_minutes'    => $totalMinutes,
            ],
            'tasksByStatus'   => $tasksByStatus,
            'tasksByCategory' => $tasksByCategory,
            'teamMembers'     => $teamMembers,
            'recentTasks'     => $recentTasks,
        ]);
    }
}
