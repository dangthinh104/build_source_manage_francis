<?php

declare(strict_types=1);

namespace App\Http\Requests\BuildGroup;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for triggering a build for a Build Group
 */
class BuildGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // User must be authenticated and have permission to build sites
        return auth()->check() && 
               (auth()->user()->hasAdminPrivileges() || auth()->user()->can('build_mysites'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // No additional input validation needed for build action
        // Group ID comes from route parameter
        return [];
    }
}
