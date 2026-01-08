<?php

declare(strict_types=1);

namespace App\Http\Requests\EnvVariable;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for updating an Environment Variable
 */
class UpdateEnvVariableRequest extends FormRequest
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
        return [
            'variable_value' => 'required|string',
            'group_name' => 'nullable|string|max:255',
            'my_site_id' => 'nullable|integer|exists:my_site,id',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Business Rule: group_name and my_site_id are mutually exclusive
            if ($this->filled('group_name') && $this->filled('my_site_id')) {
                $validator->errors()->add(
                    'scope',
                    'A variable cannot be both group-scoped and site-specific. Please choose one or leave both empty for a global variable.'
                );
            }
        });
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'variable_value' => 'variable value',
            'group_name' => 'group name',
            'my_site_id' => 'site',
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
            'my_site_id.exists' => 'The selected site does not exist.',
        ];
    }
}
