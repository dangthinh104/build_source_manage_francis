<?php

declare(strict_types=1);

namespace App\Http\Requests\EnvVariable;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for deleting an Environment Variable
 */
class DeleteEnvVariableRequest extends FormRequest
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
        // No additional validation needed for delete
        return [];
    }
}
