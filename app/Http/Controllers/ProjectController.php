<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Display a listing of the projects.
     */
    public function index(): Response
    {
        $projects = Project::with('owner:id,name')
            ->where('organization_id', auth()->user()->organization_id)
            ->latest()
            ->get()
            ->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
                'project_id' => $project->project_id,
                'description' => $project->description,
                'status' => $project->status,
                'logo' => $project->logo,
                'owner' => $project->owner->name,
                'created_at' => $project->created_at->format('M d, Y'),
            ]);

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
        ]);
    }

    /**
     * Store a newly created project.
     */
    public function store(StoreProjectRequest $request): RedirectResponse
    {
        // Check if user has permission to manage projects
        if (!auth()->user()->canManageProjects()) {
            abort(403, 'You do not have permission to create projects.');
        }

        $validated = $request->validated();

        // Set organization and owner
        $validated['organization_id'] = auth()->user()->organization_id;
        $validated['user_id'] = auth()->id();

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('projects', 'public');
        }

        Project::create($validated);

        return back()->with('success', 'Project created successfully.');
    }

    /**
     * Update the specified project.
     */
    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        // Check if user has permission to manage projects
        if (!auth()->user()->canManageProjects()) {
            abort(403, 'You do not have permission to update projects.');
        }

        // Ensure user can only update projects in their organization
        if ($project->organization_id !== auth()->user()->organization_id) {
            abort(403);
        }

        $validated = $request->validated();

        // Handle logo upload only if a new file is provided
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($project->logo) {
                Storage::disk('public')->delete($project->logo);
            }

            $validated['logo'] = $request->file('logo')->store('projects', 'public');
        } else {
            // Don't update logo field if no new file is uploaded
            unset($validated['logo']);
        }

        $project->update($validated);

        return back()->with('success', 'Project updated successfully.');
    }

    /**
     * Remove the specified project.
     */
    public function destroy(Project $project): RedirectResponse
    {
        // Check if user has permission to manage projects
        if (!auth()->user()->canManageProjects()) {
            abort(403, 'You do not have permission to delete projects.');
        }

        // Ensure user can only delete projects in their organization
        if ($project->organization_id !== auth()->user()->organization_id) {
            abort(403);
        }

        // Delete logo if exists
        if ($project->logo) {
            Storage::disk('public')->delete($project->logo);
        }

        $project->delete();

        return back()->with('success', 'Project deleted successfully.');
    }
}
