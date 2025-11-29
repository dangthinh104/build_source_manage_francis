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
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        // keep legacy flag if present
        'two_factor_enabled',
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
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
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
     * Check if the user has enabled two-factor authentication (confirmed).
     *
     * @return bool
     */
    public function hasEnabledTwoFactor(): bool
    {
        return $this->two_factor_confirmed_at !== null;
    }

    /**
     * Determine if the user should be redirected to the two-factor setup flow.
     * Returns true when 2FA is enabled but not yet confirmed.
     */
    public function shouldRedirectToTwoFactorSetup(): bool
    {
        return (bool) $this->two_factor_enabled && $this->two_factor_confirmed_at === null;
    }

    /**
     * Determine if the user should be redirected to the two-factor challenge flow.
     * Returns true when 2FA is enabled and already confirmed.
     */
    public function shouldRedirectToTwoFactorChallenge(): bool
    {
        return (bool) $this->two_factor_enabled && $this->two_factor_confirmed_at !== null;
    }
}
