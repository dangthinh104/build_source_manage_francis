<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function preference(): HasOne
    {
        return $this->hasOne(UserPreference::class);
    }

    public function buildHistories()
    {
        return $this->hasMany(\App\Models\BuildHistory::class, 'user_id');
    }

    public function isAdmin(): bool
    {
        return strtolower($this->role ?? '') === 'admin' || strtolower($this->role ?? '') === 'super_admin';
    }

    public function isSuperAdmin(): bool
    {
        return strtolower($this->role ?? '') === 'super_admin';
    }

    /**
     * Check if the user has enabled two-factor authentication.
     *
     * @return bool
     */
    public function hasEnabledTwoFactor(): bool
    {
        return !is_null($this->two_factor_secret);
    }
}
