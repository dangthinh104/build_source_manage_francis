<?php

declare(strict_types=1);

namespace App\Http\Requests\Parameter;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for deleting a Parameter
 */
class DeleteParameterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isSuperAdmin();
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
