<?php

declare(strict_types=1);

namespace App\Http\Requests\BuildGroup;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request for updating an existing Build Group
 */
class UpdateBuildGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only authenticated users with admin privileges can update build groups
        return auth()->check() && auth()->user()->hasAdminPrivileges();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $groupId = $this->route('id');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('build_groups', 'name')->ignore($groupId),
            ],
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
