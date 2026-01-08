<?php

declare(strict_types=1);

namespace App\Http\Requests\Parameter;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request for updating an existing Parameter
 */
class UpdateParameterRequest extends FormRequest
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
        $parameterId = $this->route('id');

        return [
            'key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('parameters', 'key')->ignore($parameterId),
            ],
            'value' => 'required|string',
            'type' => 'required|string|max:100',
            'description' => 'nullable|string',
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
            'key' => 'parameter key',
            'value' => 'parameter value',
            'type' => 'parameter type',
            'description' => 'description',
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
            'key.unique' => 'A parameter with this key already exists.',
        ];
    }
}
