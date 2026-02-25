<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Project;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function update(Request $request, $projectId, Category $category)
    {
        $project = Project::where('project_id', $projectId)
            ->where('organization_id', auth()->user()->organization_id)
            ->firstOrFail();

        // Ensure category belongs to this project
        if ($category->project_id !== $project->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:open,closed',
        ]);

        $category->update($validated);

        return redirect()->back()->with('success', 'Category updated successfully.');
    }
}
