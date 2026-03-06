<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskTimeLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportsController extends Controller
{
    /**
     * Global time report — all projects the user has access to.
     */
    public function global(Request $request)
    {
        $user = auth()->user();
        $orgId = $user->organization_id;

        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->input('date_to',   now()->toDateString());

        // Projects accessible to the user
        $projectIds = Project::where('organization_id', $orgId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('team', fn($t) => $t->where('users.id', $user->id));
            })
            ->pluck('id');

        $logs = TaskTimeLog::with(['user:id,name,avatar', 'task:id,title,project_id'])
            ->whereHas('task', fn($q) => $q->whereIn('project_id', $projectIds))
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->orderByDesc('date')
            ->get();

        // Aggregate by project
        $byProject = $logs->groupBy('task.project_id')->map(function ($projectLogs) {
            $project = Project::find($projectLogs->first()->task->project_id);
            return [
                'project_id'   => $project?->project_id,
                'project_name' => $project?->name,
                'total_minutes'=> $projectLogs->sum('time_spent'),
                'log_count'    => $projectLogs->count(),
            ];
        })->values();

        // Aggregate by user
        $byUser = $logs->groupBy('user_id')->map(function ($userLogs) {
            $u = $userLogs->first()->user;
            return [
                'user_id'       => $u->id,
                'user_name'     => $u->name,
                'user_avatar'   => $u->avatar,
                'total_minutes' => $userLogs->sum('time_spent'),
                'log_count'     => $userLogs->count(),
            ];
        })->values();

        // All projects for filter dropdown
        $projects = Project::where('organization_id', $orgId)
            ->whereIn('id', $projectIds)
            ->get(['id', 'project_id', 'name']);

        // All tasks for log-time form
        $tasks = Task::whereIn('project_id', $projectIds)
            ->whereNull('deleted_at')
            ->with('project:id,project_id,name')
            ->orderBy('title')
            ->get(['id', 'title', 'project_id']);

        // All users in org for filter
        $users = User::where('organization_id', $orgId)->get(['id', 'name', 'avatar']);

        return Inertia::render('Reports/Global', [
            'logs'      => $logs->map(fn($l) => $this->formatLog($l)),
            'byProject' => $byProject,
            'byUser'    => $byUser,
            'projects'  => $projects,
            'tasks'     => $tasks,
            'users'     => $users,
            'filters'   => ['date_from' => $dateFrom, 'date_to' => $dateTo],
            'totalMinutes' => $logs->sum('time_spent'),
        ]);
    }

    /**
     * Per-project time report.
     */
    public function project(Request $request, $projectId)
    {
        $user    = auth()->user();
        $project = Project::where('project_id', $projectId)
            ->where('organization_id', $user->organization_id)
            ->firstOrFail();

        if (!$project->isMember($user)) {
            abort(403);
        }

        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->input('date_to',   now()->toDateString());

        $logs = TaskTimeLog::with(['user:id,name,avatar', 'task:id,title,project_id'])
            ->whereHas('task', fn($q) => $q->where('project_id', $project->id))
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->orderByDesc('date')
            ->get();

        // By task
        $byTask = $logs->groupBy('task_id')->map(function ($taskLogs) {
            $t = $taskLogs->first()->task;
            return [
                'task_id'       => $t->id,
                'task_title'    => $t->title,
                'total_minutes' => $taskLogs->sum('time_spent'),
                'log_count'     => $taskLogs->count(),
            ];
        })->sortByDesc('total_minutes')->values();

        // By user
        $byUser = $logs->groupBy('user_id')->map(function ($userLogs) {
            $u = $userLogs->first()->user;
            return [
                'user_id'       => $u->id,
                'user_name'     => $u->name,
                'user_avatar'   => $u->avatar,
                'total_minutes' => $userLogs->sum('time_spent'),
                'log_count'     => $userLogs->count(),
            ];
        })->sortByDesc('total_minutes')->values();

        // Tasks for log-time form
        $tasks = Task::where('project_id', $project->id)
            ->whereNull('deleted_at')
            ->orderBy('title')
            ->get(['id', 'title', 'project_id']);

        // Project members for filter
        $members = $project->team()->get(['users.id', 'users.name', 'users.avatar']);

        return Inertia::render('Reports/Project', [
            'project'      => $project,
            'logs'         => $logs->map(fn($l) => $this->formatLog($l)),
            'byTask'       => $byTask,
            'byUser'       => $byUser,
            'tasks'        => $tasks,
            'members'      => $members,
            'filters'      => ['date_from' => $dateFrom, 'date_to' => $dateTo],
            'totalMinutes' => $logs->sum('time_spent'),
        ]);
    }

    private function formatLog(TaskTimeLog $log): array
    {
        return [
            'id'           => $log->id,
            'task_id'      => $log->task_id,
            'task_title'   => $log->task?->title,
            'project_id'   => $log->task?->project_id,
            'user_id'      => $log->user_id,
            'user_name'    => $log->user?->name,
            'user_avatar'  => $log->user?->avatar,
            'date'         => $log->date?->toDateString(),
            'time_spent'   => $log->time_spent,
            'comment'      => $log->comment,
            'created_at'   => $log->created_at,
        ];
    }
}
