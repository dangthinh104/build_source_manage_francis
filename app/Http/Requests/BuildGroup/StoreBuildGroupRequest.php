<?php

declare(strict_types=1);

namespace App\Http\Requests\BuildGroup;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for storing a new Build Group
 */
class StoreBuildGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only authenticated users with admin privileges can create build groups
        return auth()->check() && auth()->user()->hasAdminPrivileges();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:build_groups,name',
            'description' => 'nullable|string|max:1000',
            'site_ids' => 'nullable|array',
            'site_ids.*' => 'exists:my_site,id',
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
            'name' => 'group name',
            'description' => 'description',
            'site_ids' => 'selected sites',
            'site_ids.*' => 'site',
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
            'name.required' => 'Please provide a name for the build group.',
            'name.unique' => 'A build group with this name already exists.',
            'site_ids.*.exists' => 'One or more selected sites do not exist.',
        ];
    }
}
