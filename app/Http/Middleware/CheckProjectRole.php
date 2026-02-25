<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckProjectRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role = null): Response
    {
        $project = $request->route('project');

        // If project is not in route, skip check
        if (!$project instanceof Project) {
            return $next($request);
        }

        $user = $request->user();

        // Check if user has access to the project (same organization)
        if ($project->organization_id !== $user->organization_id) {
            abort(403, 'Unauthorized access to project.');
        }

        // Check if user is a member or owner
        if (!$project->isMember($user)) {
            abort(403, 'You are not a member of this project.');
        }

        // If role is specified, check if user has that role
        if ($role === 'manager' && !$project->isManager($user)) {
            abort(403, 'This action requires manager privileges.');
        }

        return $next($request);
    }
}
