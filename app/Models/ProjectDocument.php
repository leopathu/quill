<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'user_id',
        'parent_id',
        'title',
        'content',
        'order',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id')->select(['id', 'name', 'avatar']);
    }

    public function parent()
    {
        return $this->belongsTo(ProjectDocument::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ProjectDocument::class, 'parent_id')
            ->orderBy('order')
            ->orderBy('title');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'document_tag', 'document_id', 'tag_id');
    }
}
