<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskTimeLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $orgId = $user->organization_id;

        // ── Date range from filter ─────────────────────────────────────────
        [$from, $to] = $this->getDateRange($request->get('period', 'this_month'));

        // ── Projects accessible to user ────────────────────────────────────
        $allProjects = Project::where('organization_id', $orgId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('team', fn ($t) => $t->where('users.id', $user->id));
            })
            ->with('owner:id,name,avatar')
            ->get();

        $projectIds = $allProjects->pluck('id');

        // ── Task stats (period-scoped) ─────────────────────────────────────
        $tasksInPeriod = Task::whereIn('project_id', $projectIds)
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $allTasks = Task::whereIn('project_id', $projectIds)->get();

        $completedInPeriod = Task::whereIn('project_id', $projectIds)
            ->where('status', 'Completed')
            ->whereBetween('updated_at', [$from, $to])
            ->count();

        // ── Time logs (period-scoped) ──────────────────────────────────────
        $timeInPeriod = TaskTimeLog::whereHas('task', fn ($q) => $q->whereIn('project_id', $projectIds))
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->sum('time_spent');

        // ── Stats by project ───────────────────────────────────────────────
        $projectStats = $allProjects->map(function ($project) use ($from, $to) {
            $tasks        = Task::where('project_id', $project->id)->get();
            $total        = $tasks->count();
            $completed    = $tasks->where('status', 'Completed')->count();
            $inProgress   = $tasks->where('status', 'In Progress')->count();
            $todo         = $tasks->where('status', 'Todo')->count();
            $newInPeriod  = Task::where('project_id', $project->id)
                ->whereBetween('created_at', [$from, $to])->count();
            $timeLogged   = TaskTimeLog::whereHas('task', fn ($q) => $q->where('project_id', $project->id))
                ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                ->sum('time_spent');
            $memberCount  = $project->team()->count() + 1; // +1 owner

            return [
                'id'            => $project->id,
                'name'          => $project->name,
                'project_id'    => $project->project_id,
                'status'        => $project->status,
                'logo'          => $project->logo,
                'owner'         => $project->owner,
                'total_tasks'   => $total,
                'completed'     => $completed,
                'in_progress'   => $inProgress,
                'todo'          => $todo,
                'new_in_period' => $newInPeriod,
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100) : 0,
                'time_logged'   => $timeLogged,
                'member_count'  => $memberCount,
            ];
        })->sortByDesc('completion_rate')->values();

        // ── Tasks by status (all time) ─────────────────────────────────────
        $tasksByStatus = $allTasks->groupBy('status')->map->count()->toArray();

        // ── Tasks created per day in period ───────────────────────────────
        $tasksTrend = Task::whereIn('project_id', $projectIds)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        $completedTrend = Task::whereIn('project_id', $projectIds)
            ->where('status', 'Completed')
            ->whereBetween('updated_at', [$from, $to])
            ->selectRaw('DATE(updated_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        // Fill all days in range
        $trendDates = [];
        $trendCreated = [];
        $trendCompleted = [];
        $days = $from->copy();
        while ($days->lte($to)) {
            $d = $days->toDateString();
            $trendDates[]    = $days->format('d M');
            $trendCreated[]  = $tasksTrend[$d] ?? 0;
            $trendCompleted[] = $completedTrend[$d] ?? 0;
            $days->addDay();
        }

        // Limit to max 60 points for readability — group by week if longer
        if (count($trendDates) > 60) {
            [$trendDates, $trendCreated, $trendCompleted] = $this->groupByWeek($from, $to, $projectIds);
        }

        // ── Top team members by tasks completed ────────────────────────────
        $teamStats = User::where('organization_id', $orgId)
            ->get()
            ->map(function ($u) use ($projectIds, $from, $to) {
                $assigned  = Task::whereIn('project_id', $projectIds)->where('assignee_id', $u->id)->count();
                $completed = Task::whereIn('project_id', $projectIds)
                    ->where('assignee_id', $u->id)
                    ->where('status', 'Completed')
                    ->whereBetween('updated_at', [$from, $to])
                    ->count();
                $time = TaskTimeLog::where('user_id', $u->id)
                    ->whereHas('task', fn ($q) => $q->whereIn('project_id', $projectIds))
                    ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                    ->sum('time_spent');
                return [
                    'id'        => $u->id,
                    'name'      => $u->name,
                    'avatar'    => $u->avatar,
                    'assigned'  => $assigned,
                    'completed' => $completed,
                    'time'      => $time,
                ];
            })
            ->filter(fn ($u) => $u['assigned'] > 0 || $u['time'] > 0)
            ->sortByDesc('completed')
            ->take(8)
            ->values();

        return Inertia::render('Dashboard', [
            'period'      => $request->get('period', 'this_month'),
            'dateRange'   => ['from' => $from->format('M d, Y'), 'to' => $to->format('M d, Y')],
            'stats' => [
                'total_projects'    => $allProjects->count(),
                'active_projects'   => $allProjects->where('status', 'Progressing')->count(),
                'total_tasks'       => $allTasks->count(),
                'tasks_in_period'   => $tasksInPeriod->count(),
                'completed_in_period' => $completedInPeriod,
                'todo'              => $allTasks->where('status', 'Todo')->count(),
                'in_progress'       => $allTasks->where('status', 'In Progress')->count(),
                'completed_all'     => $allTasks->where('status', 'Completed')->count(),
                'completion_rate'   => $allTasks->count() > 0
                    ? round(($allTasks->where('status', 'Completed')->count() / $allTasks->count()) * 100) : 0,
                'time_in_period'    => $timeInPeriod,
                'team_count'        => User::where('organization_id', $orgId)->count(),
            ],
            'tasksByStatus'  => $tasksByStatus,
            'projectStats'   => $projectStats,
            'teamStats'      => $teamStats,
            'trend' => [
                'labels'    => $trendDates,
                'created'   => $trendCreated,
                'completed' => $trendCompleted,
            ],
        ]);
    }

    private function getDateRange(string $period): array
    {
        $now = Carbon::now();
        return match ($period) {
            'this_week'     => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'last_week'     => [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()],
            'this_month'    => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month'    => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            'this_quarter'  => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'last_quarter'  => [$now->copy()->subQuarter()->startOfQuarter(), $now->copy()->subQuarter()->endOfQuarter()],
            'this_year'     => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'last_year'     => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
            default         => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    private function groupByWeek(Carbon $from, Carbon $to, $projectIds): array
    {
        $labels = $created = $completed = [];
        $cursor = $from->copy()->startOfWeek();
        while ($cursor->lte($to)) {
            $weekEnd = $cursor->copy()->endOfWeek()->min($to);
            $labels[]    = $cursor->format('d M');
            $created[]   = Task::whereIn('project_id', $projectIds)
                ->whereBetween('created_at', [$cursor, $weekEnd])->count();
            $completed[]  = Task::whereIn('project_id', $projectIds)
                ->where('status', 'Completed')
                ->whereBetween('updated_at', [$cursor, $weekEnd])->count();
            $cursor->addWeek();
        }
        return [$labels, $created, $completed];
    }
}
