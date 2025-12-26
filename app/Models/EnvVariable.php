<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model for storing environment variables with encrypted values.
 * 
 * Security: variable_value is encrypted at rest using custom encryptValue/decryptValue helpers.
 * NOTE: Using custom helpers instead of Laravel's encrypted cast for backward compatibility
 * with existing encrypted data.
 */
class EnvVariable extends Model
{
    use HasFactory;

    protected $fillable = [
        'variable_name',
        'variable_value',
        'group_name',
        'my_site_id',
    ];

    // No 'encrypted' cast - using custom encryptValue/decryptValue helpers for backward compatibility

    /**
     * Relationship: belongs to a specific site (optional)
     */
    public function mySite()
    {
        return $this->belongsTo(MySite::class, 'my_site_id');
    }

    /**
     * Scope: Global variables (no group, no site)
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('group_name')->whereNull('my_site_id');
    }

    /**
     * Scope: Variables for a specific group
     */
    public function scopeForGroup($query, string $groupName)
    {
        return $query->where('group_name', $groupName)->whereNull('my_site_id');
    }

    /**
     * Scope: Variables for a specific site
     */
    public function scopeForSite($query, int $siteId)
    {
        return $query->where('my_site_id', $siteId)->whereNull('group_name');
    }
}
