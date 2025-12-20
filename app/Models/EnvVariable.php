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
    ];

    // No 'encrypted' cast - using custom encryptValue/decryptValue helpers for backward compatibility
}
