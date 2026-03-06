<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskTimeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'date',
        'time_spent',
        'comment',
    ];

    protected $casts = [
        'date' => 'date',
        'time_spent' => 'integer',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->select(['id', 'name', 'avatar']);
    }

    /**
     * Get time_spent formatted as human-readable string (e.g. "2h 30m").
     */
    public function getFormattedTimeAttribute(): string
    {
        $h = intdiv($this->time_spent, 60);
        $m = $this->time_spent % 60;
        if ($h > 0 && $m > 0) return "{$h}h {$m}m";
        if ($h > 0) return "{$h}h";
        return "{$m}m";
    }
}
