<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'category_id',
        'owner_id',
        'assignee_id',
        'title',
        'description',
        'attachments',
        'status',
        'estimation',
    ];

    protected $casts = [
        'attachments' => 'array',
        'estimation' => 'decimal:2',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'task_tag')->withTimestamps();
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class)
            ->whereNull('parent_id')
            ->with('user', 'replies')
            ->latest();
    }
}
