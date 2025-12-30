<?php

declare(strict_types=1);

namespace App\Http\Requests\MySite;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request for creating a new site
 */
class StoreSiteRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only super_admin can create sites
        return $this->user() && $this->user()->isSuperAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'site_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\-_.]+$/',
                Rule::unique('my_site', 'site_name'),
            ],
            'path_source_code' => [
                'required',
                'string',
                'max:500',
            ],
            'include_pm2' => [
                'required',
                'boolean',
            ],
            'port_pm2' => [
                'nullable',
                'integer',
                'min:1024',
                'max:65535',
                Rule::unique('my_site', 'port_pm2'),
            ],
            'api_endpoint_url' => [
                'nullable',
                'url',
                'max:500',
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
            'site_name.required' => 'Site name is required',
            'site_name.unique' => 'Site name already exists',
            'site_name.regex' => 'Site name can only contain letters, numbers, hyphens, underscores, and dots',
            'path_source_code.required' => 'Source code path is required',
            'port_pm2.unique' => 'Port is already in use by another site',
            'port_pm2.min' => 'Port must be at least 1024',
            'port_pm2.max' => 'Port must not exceed 65535',
            'api_endpoint_url.url' => 'API endpoint must be a valid URL',
        ];
    }
}
