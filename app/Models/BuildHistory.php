<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuildHistory extends Model
{
    protected $table = 'build_histories';

    protected $fillable = [
        'site_id',
        'user_id',
        'status',
        'output_log',
    ];

    protected $appends = ['duration'];

    public function site()
    {
        return $this->belongsTo(MySite::class, 'site_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the duration of the build (difference between updated_at and created_at)
     * Returns human-readable format like "2s", "1m 30s", etc.
     */
    public function getDurationAttribute(): string
    {
        if (!$this->created_at || !$this->updated_at) {
            return '—';
        }

        $seconds = $this->updated_at->diffInSeconds($this->created_at);

        if ($seconds < 60) {
            return $seconds . 's';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return $remainingSeconds > 0 ? "{$minutes}m {$remainingSeconds}s" : "{$minutes}m";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return "{$hours}h {$remainingMinutes}m";
    }
}
