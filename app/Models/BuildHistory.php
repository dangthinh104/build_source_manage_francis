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

    public function site()
    {
        return $this->belongsTo(MySite::class, 'site_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
