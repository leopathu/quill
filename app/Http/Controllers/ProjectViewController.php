<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Inertia\Inertia;
use Inertia\Response;

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

        return Inertia::render('Projects/View', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'project_id' => $project->project_id,
                'description' => $project->description,
                'status' => $project->status,
                'logo' => $project->logo,
                'owner' => [
                    'name' => $project->owner->name,
                    'avatar' => $project->owner->avatar,
                ],
                'created_at' => $project->created_at->format('M d, Y'),
            ],
        ]);
    }
}
