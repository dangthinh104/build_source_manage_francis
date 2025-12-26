<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MySite extends Model
{
    protected $table = 'my_site';

    public $timestamps = true;

    protected $guarded = [];

    protected $appends = ['last_build_success_ago', 'last_build_fail_ago'];

    protected $casts = [
        'last_build_success' => 'datetime',
        'last_build_fail' => 'datetime',
        'last_build' => 'datetime',
    ];

    public function buildHistories()
    {
        return $this->hasMany(BuildHistory::class, 'site_id');
    }

    public function lastBuilder()
    {
        return $this->belongsTo(User::class, 'last_user_build');
    }

    public function buildGroups()
    {
        return $this->belongsToMany(BuildGroup::class, 'build_group_sites', 'my_site_id', 'build_group_id')
                    ->withTimestamps();
    }

    public function envVariables()
    {
        return $this->hasMany(EnvVariable::class, 'my_site_id');
    }

    /**
     * Get human-readable time for last successful build
     */
    public function getLastBuildSuccessAgoAttribute(): ?string
    {
        return $this->last_build_success?->diffForHumans();
    }

    /**
     * Get human-readable time for last failed build
     */
    public function getLastBuildFailAgoAttribute(): ?string
    {
        return $this->last_build_fail?->diffForHumans();
    }
}
