<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Base Form Request
 * 
 * Provides standardized validation error responses following JSend format.
 */
abstract class BaseFormRequest extends FormRequest
{
    /**
     * Handle a failed validation attempt.
     *
     * For Inertia requests, use default Laravel/Inertia behavior (redirect with session errors).
     * For AJAX/API requests, return JSON response in JSend format.
     *
     * @param Validator $validator
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        // For Inertia requests, use default Laravel behavior (redirect with errors in session)
        if ($this->hasHeader('X-Inertia')) {
            parent::failedValidation($validator);
            return;
        }

        // For AJAX/API requests, return JSON
        throw new HttpResponseException(
            response()->json([
                'status' => 'fail',
                'message' => 'Validation failed',
                'errors' => $validator->errors()->toArray(),
            ], 422)
        );
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @throws HttpResponseException
     */
    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(
            response()->json([
                'status' => 'fail',
                'message' => 'This action is unauthorized.',
            ], 403)
        );
    }
}
