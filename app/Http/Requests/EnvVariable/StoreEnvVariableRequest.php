<?php

declare(strict_types=1);

namespace App\Http\Requests\EnvVariable;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for storing a new Environment Variable
 */
class StoreEnvVariableRequest extends FormRequest
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
            'variable_name' => 'required|string|max:255',
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
            // Business Rule 1: group_name and my_site_id are mutually exclusive
            if ($this->filled('group_name') && $this->filled('my_site_id')) {
                $validator->errors()->add(
                    'scope',
                    'A variable cannot be both group-scoped and site-specific. Please choose one or leave both empty for a global variable.'
                );
            }

            // Business Rule 2: Check for duplicate in the same scope
            // Use model directly to avoid calling protected repository methods
            $existingVar = \App\Models\EnvVariable::query()
                ->where('variable_name', $this->input('variable_name'))
                ->where('group_name', $this->input('group_name'))
                ->where('my_site_id', $this->input('my_site_id'))
                ->first();

            if ($existingVar) {
                $validator->errors()->add(
                    'variable_name',
                    'A variable with this name already exists in the same scope.'
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
            'variable_name' => 'variable name',
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
