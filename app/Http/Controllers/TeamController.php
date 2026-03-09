<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Services\EmailNotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TeamController extends Controller
{
    /**
     * Display the team members for a project.
     */
    public function index(Request $request, $projectId)
    {
        $project = Project::where('project_id', $projectId)
            ->where('organization_id', $request->user()->organization_id)
            ->with('owner')
            ->firstOrFail();

        // Check if user has access to the project
        if (!$project->isMember($request->user())) {
            abort(403, 'You are not a member of this project.');
        }

        // Get team members with their roles
        $teamMembers = $project->team()
            ->withPivot('role')
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'role' => $user->pivot->role,
                ];
            });

        // Add project owner if not already in team
        $owner = $project->owner;
        $ownerInTeam = $teamMembers->contains('id', $owner->id);
        
        if (!$ownerInTeam) {
            $teamMembers->prepend([
                'id' => $owner->id,
                'name' => $owner->name,
                'email' => $owner->email,
                'avatar' => $owner->avatar,
                'role' => 'manager',
                'is_owner' => true,
            ]);
        }

        // Get available users from organization (excluding already added members)
        $availableUsers = User::where('organization_id', $project->organization_id)
            ->whereNotIn('id', $teamMembers->pluck('id'))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'avatar']);

        return Inertia::render('Projects/Teams/Index', [
            'project' => $project->load('owner'),
            'teamMembers' => $teamMembers,
            'availableUsers' => $availableUsers,
            'userRole' => $project->getUserRole($request->user()),
        ]);
    }

    /**
     * Add a user to the project team.
     */
    public function store(Request $request, $projectId)
    {
        $project = Project::where('project_id', $projectId)
            ->where('organization_id', $request->user()->organization_id)
            ->firstOrFail();

        // Check if user is a manager
        if (!$project->isManager($request->user())) {
            abort(403, 'Only managers can add team members.');
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:manager,developer',
        ]);

        // Check if user is from the same organization
        $userToAdd = User::findOrFail($validated['user_id']);
        if ($userToAdd->organization_id !== $project->organization_id) {
            return redirect()->back()->with('error', 'Can only add users from the same organization.');
        }

        // Check if user is already a team member
        if ($project->team()->where('user_id', $validated['user_id'])->exists()) {
            return redirect()->back()->with('error', 'User is already a team member.');
        }

        // Add user to team
        $project->team()->attach($validated['user_id'], ['role' => $validated['role']]);

        // Email notification
        $org = $request->user()->organization;
        (new EmailNotificationService())->teamMemberAdded($userToAdd, $project, $org, $validated['role']);

        return redirect()->back()->with('success', 'Team member added successfully.');
    }

    /**
     * Update a team member's role.
     */
    public function update(Request $request, $projectId, User $user)
    {
        $project = Project::where('project_id', $projectId)
            ->where('organization_id', $request->user()->organization_id)
            ->firstOrFail();

        // Check if user is a manager
        if (!$project->isManager($request->user())) {
            abort(403, 'Only project managers can update team member roles.');
        }

        $validated = $request->validate([
            'role' => 'required|in:manager,developer',
        ]);

        // Check if user is a team member
        if (!$project->team()->where('user_id', $user->id)->exists()) {
            return redirect()->back()->with('error', 'User is not a team member.');
        }

        // Update role
        $project->team()->updateExistingPivot($user->id, ['role' => $validated['role']]);

        return redirect()->back()->with('success', 'Team member role updated successfully.');
    }

    /**
     * Remove a user from the project team.
     */
    public function destroy(Request $request, $projectId, User $user)
    {
        $project = Project::where('project_id', $projectId)
            ->where('organization_id', $request->user()->organization_id)
            ->firstOrFail();

        // Check if user is a manager
        if (!$project->isManager($request->user())) {
            abort(403, 'Only managers can remove team members.');
        }

        // Prevent removing the project owner
        if ($project->user_id === $user->id) {
            return redirect()->back()->with('error', 'Cannot remove the project owner.');
        }

        // Check if user is a team member
        if (!$project->team()->where('user_id', $user->id)->exists()) {
            return redirect()->back()->with('error', 'User is not a team member.');
        }

        // Remove user from team
        $project->team()->detach($user->id);

        // Email notification
        $org = $request->user()->organization;
        (new EmailNotificationService())->teamMemberRemoved($user, $project, $org);

        return redirect()->back()->with('success', 'Team member removed successfully.');
    }
}
