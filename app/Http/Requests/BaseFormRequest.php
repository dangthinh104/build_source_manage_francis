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
     * @param Validator $validator
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
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
