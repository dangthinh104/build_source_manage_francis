<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MySite extends Model
{
    protected $table = 'my_site';

    public $timestamps = true;

    protected $guarded = [];

    public function buildHistories()
    {
        return $this->hasMany(BuildHistory::class, 'site_id');
    }

    public function lastBuilder()
    {
        return $this->belongsTo(User::class, 'last_user_build');
    }
}
