<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DocumentController extends Controller
{
    private function getProject($projectId)
    {
        $project = Project::where('project_id', $projectId)
            ->where('organization_id', auth()->user()->organization_id)
            ->firstOrFail();

        if (!$project->isMember(auth()->user())) {
            abort(403);
        }

        return $project;
    }

    /**
     * Build a nested tree of documents for a project.
     */
    private function buildTree($projectId, $parentId = null): array
    {
        $docs = ProjectDocument::where('project_id', $projectId)
            ->where('parent_id', $parentId)
            ->orderBy('order')
            ->orderBy('title')
            ->with('author:id,name,avatar', 'tags:id,name,color')
            ->get();

        return $docs->map(function ($doc) use ($projectId) {
            return [
                'id'         => $doc->id,
                'title'      => $doc->title,
                'parent_id'  => $doc->parent_id,
                'order'      => $doc->order,
                'author'     => $doc->author,
                'tags'       => $doc->tags,
                'updated_at' => $doc->updated_at,
                'children'   => $this->buildTree($projectId, $doc->id),
            ];
        })->values()->toArray();
    }

    public function index(Request $request, $projectId)
    {
        $project = $this->getProject($projectId);

        $tree = $this->buildTree($project->id);
        $tags = Tag::where('project_id', $project->id)->orderBy('name')->get(['id', 'name', 'color']);

        // Load first document if none specified
        $firstDoc = ProjectDocument::where('project_id', $project->id)
            ->orderBy('order')->orderBy('title')
            ->first();

        return Inertia::render('Projects/Documents/Index', [
            'project'     => $project,
            'tree'        => $tree,
            'tags'        => $tags,
            'activeDoc'   => $firstDoc ? $this->formatDoc($firstDoc) : null,
        ]);
    }

    public function show(Request $request, $projectId, ProjectDocument $document)
    {
        $project = $this->getProject($projectId);

        if ($document->project_id !== $project->id) {
            abort(403);
        }

        $document->load('author:id,name,avatar', 'tags:id,name,color');
        $tree = $this->buildTree($project->id);
        $tags = Tag::where('project_id', $project->id)->orderBy('name')->get(['id', 'name', 'color']);

        return Inertia::render('Projects/Documents/Index', [
            'project'   => $project,
            'tree'      => $tree,
            'tags'      => $tags,
            'activeDoc' => $this->formatDoc($document),
        ]);
    }

    public function store(Request $request, $projectId)
    {
        $project = $this->getProject($projectId);

        $validated = $request->validate([
            'title'     => 'required|string|max:255',
            'content'   => 'nullable|string',
            'parent_id' => 'nullable|exists:project_documents,id',
            'tag_ids'   => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
        ]);

        // Validate parent belongs to this project
        if (!empty($validated['parent_id'])) {
            $parent = ProjectDocument::findOrFail($validated['parent_id']);
            if ($parent->project_id !== $project->id) abort(403);
        }

        $doc = ProjectDocument::create([
            'project_id' => $project->id,
            'user_id'    => auth()->id(),
            'parent_id'  => $validated['parent_id'] ?? null,
            'title'      => $validated['title'],
            'content'    => $validated['content'] ?? null,
            'order'      => 0,
        ]);

        if (!empty($validated['tag_ids'])) {
            $doc->tags()->sync($validated['tag_ids']);
        }

        return redirect()->route('project.documents.show', [
            'projectId' => $projectId,
            'document'  => $doc->id,
        ]);
    }

    public function update(Request $request, $projectId, ProjectDocument $document)
    {
        $project = $this->getProject($projectId);

        if ($document->project_id !== $project->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title'     => 'required|string|max:255',
            'content'   => 'nullable|string',
            'parent_id' => 'nullable|exists:project_documents,id',
            'tag_ids'   => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
        ]);

        // Prevent circular nesting
        if (!empty($validated['parent_id']) && $validated['parent_id'] == $document->id) {
            abort(422, 'A document cannot be its own parent.');
        }

        $document->update([
            'title'     => $validated['title'],
            'content'   => $validated['content'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        $document->tags()->sync($validated['tag_ids'] ?? []);

        return redirect()->route('project.documents.show', [
            'projectId' => $projectId,
            'document'  => $document->id,
        ]);
    }

    public function destroy(Request $request, $projectId, ProjectDocument $document)
    {
        $project = $this->getProject($projectId);

        if ($document->project_id !== $project->id) {
            abort(403);
        }

        // Re-parent children to this doc's parent
        ProjectDocument::where('parent_id', $document->id)
            ->update(['parent_id' => $document->parent_id]);

        $document->delete();

        return redirect()->route('project.documents.index', $projectId);
    }

    private function formatDoc(ProjectDocument $doc): array
    {
        return [
            'id'         => $doc->id,
            'title'      => $doc->title,
            'content'    => $doc->content,
            'parent_id'  => $doc->parent_id,
            'order'      => $doc->order,
            'author'     => $doc->author,
            'tags'       => $doc->tags ?? [],
            'updated_at' => $doc->updated_at,
            'created_at' => $doc->created_at,
        ];
    }
}
