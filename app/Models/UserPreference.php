<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'theme_color',
        'content_width',
        'sidebar_style',
        'compact_mode',
    ];

    protected $casts = [
        'compact_mode' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getDefaults(): array
    {
        return [
            'theme_color' => 'indigo',
            'content_width' => 'wide',
            'sidebar_style' => 'gradient',
            'compact_mode' => false,
        ];
    }
}
