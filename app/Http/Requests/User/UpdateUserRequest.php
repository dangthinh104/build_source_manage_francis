<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request for updating an existing User
 */
class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAdminPrivileges();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Get user ID from route parameter
        // Route might use either 'id' or 'user' as parameter name
        $userId = $this->route('id') ?? $this->route('user');
        
        // Cast to int for safety
        $userId = (int) $userId;

        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                // Always apply unique validation, but always ignore current user
                // This handles both cases: email unchanged (ignored) and email changed (checked against others)
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'role' => 'nullable|in:' . User::ROLE_USER . ',' . User::ROLE_ADMIN . ',' . User::ROLE_SUPER_ADMIN,
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'name',
            'email' => 'email address',
            'role' => 'role',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'This email address is already taken by another user.',
        ];
    }
}
