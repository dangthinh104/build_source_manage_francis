<?php

declare(strict_types=1);

namespace App\Http\Requests\MySite;

use App\Http\Requests\BaseFormRequest;

/**
 * Form Request for getting site details/logs/history
 * Reusable for multiple endpoints that need site_id
 */
class GetSiteDataRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // All authenticated users can view site data
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'site_id' => [
                'required',
                'integer',
                'exists:my_site,id',
            ],
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
            'site_id.required' => 'Site ID is required',
            'site_id.exists' => 'Site not found',
        ];
    }
}
