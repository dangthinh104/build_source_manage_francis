<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuildGroup extends Model
{
    protected $fillable = ['name', 'description', 'user_id'];

    public function sites()
    {
        return $this->belongsToMany(MySite::class, 'build_group_sites', 'build_group_id', 'my_site_id')
                    ->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
