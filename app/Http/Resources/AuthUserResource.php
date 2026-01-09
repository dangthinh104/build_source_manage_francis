<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for authenticated user data shared via Inertia.
 * 
 * Only exposes necessary fields to prevent data leakage.
 * Sensitive fields like email, timestamps, 2FA status are excluded.
 */
class AuthUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role,
            'must_change_password' => $this->must_change_password,
            // Preference loaded separately
        ];
    }
}
