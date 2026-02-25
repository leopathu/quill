<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the users that have this role.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Check if this role is Admin.
     */
    public function isAdmin(): bool
    {
        return $this->name === 'Admin';
    }

    /**
     * Check if this role is Team.
     */
    public function isTeam(): bool
    {
        return $this->name === 'Team';
    }
}
