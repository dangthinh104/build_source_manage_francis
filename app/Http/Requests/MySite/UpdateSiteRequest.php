<?php

declare(strict_types=1);

namespace App\Http\Requests\MySite;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request for updating an existing site
 */
class UpdateSiteRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Admin and Super Admin can edit sites
        return $this->user() && $this->user()->hasAdminPrivileges();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $siteId = $this->input('id');

        return [
            'id' => [
                'required',
                'integer',
                'exists:my_site,id',
            ],
            'site_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\-_.]+$/',
                Rule::unique('my_site', 'site_name')->ignore($siteId),
            ],
            'port_pm2' => [
                'nullable',
                'integer',
                'min:1024',
                'max:65535',
                Rule::unique('my_site', 'port_pm2')->ignore($siteId),
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
            'id.required' => 'Site ID is required',
            'id.exists' => 'Site not found',
            'site_name.required' => 'Site name is required',
            'site_name.unique' => 'Site name already exists',
            'site_name.regex' => 'Site name can only contain letters, numbers, hyphens, underscores, and dots',
            'port_pm2.unique' => 'Port is already in use by another site',
            'port_pm2.min' => 'Port must be at least 1024',
            'port_pm2.max' => 'Port must not exceed 65535',
            'api_endpoint_url.url' => 'API endpoint must be a valid URL',
        ];
    }
}
