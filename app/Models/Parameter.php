<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parameter extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    protected $casts = [
        'key' => 'string',
        'value' => 'string',
        'type' => 'string',
        'description' => 'string',
    ];

    /**
     * Get a parameter value by key, with optional default fallback.
     * 
     * @param string $key The parameter key to look up
     * @param mixed $default Default value if key not found
     * @return mixed The parameter value or default
     */
    public static function getValue(string $key, $default = null)
    {
        try {
            return static::where('key', $key)->value('value') ?? $default;
        } catch (\Exception $e) {
            return $default;
        }
    }
}
