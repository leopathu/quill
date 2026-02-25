<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'user_id',
        'name',
        'project_id',
        'description',
        'status',
        'logo',
    ];

    /**
     * Get the organization that owns the project.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user who owns the project.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the tasks for the project.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the categories for the project.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get the tags for the project.
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * Get the team members for the project.
     */
    public function team(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get only the managers for the project.
     */
    public function managers(): BelongsToMany
    {
        return $this->team()->wherePivot('role', 'manager');
    }

    /**
     * Get only the developers for the project.
     */
    public function developers(): BelongsToMany
    {
        return $this->team()->wherePivot('role', 'developer');
    }

    /**
     * Check if a user is a manager of the project.
     */
    public function isManager(User $user): bool
    {
        return $this->team()
            ->wherePivot('user_id', $user->id)
            ->wherePivot('role', 'manager')
            ->exists() || $this->user_id === $user->id;
    }

    /**
     * Check if a user is a team member of the project.
     */
    public function isMember(User $user): bool
    {
        return $this->team()->where('user_id', $user->id)->exists() 
            || $this->user_id === $user->id;
    }

    /**
     * Get the user's role in the project.
     */
    public function getUserRole(User $user): ?string
    {
        if ($this->user_id === $user->id) {
            return 'manager'; // Owner is always a manager
        }

        $member = $this->team()->where('user_id', $user->id)->first();
        return $member?->pivot->role;
    }
}
