<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Base Controller
 * 
 * Provides standardized response methods following JSend specification.
 * All controllers should extend this class for unified response handling.
 * 
 * Response format:
 * - success: {status: "success", message?: string, data?: any}
 * - fail: {status: "fail", message: string, errors?: object}
 * - error: {status: "error", message: string, debug?: object}
 */
abstract class BaseController extends Controller
{
    /**
     * Return a success JSON response
     *
     * @param mixed $data Response data (optional)
     * @param string|null $message Success message (optional)
     * @param int $statusCode HTTP status code
     * @return JsonResponse
     */
    protected function success(
        mixed $data = null,
        ?string $message = null,
        int $statusCode = 200
    ): JsonResponse {
        $response = ['status' => 'success'];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a fail JSON response (client error)
     * 
     * Use for validation errors, authorization failures, not found, etc.
     *
     * @param string $message Error message
     * @param mixed $errors Additional error details (validation errors, etc.)
     * @param int $statusCode HTTP status code (4xx)
     * @return JsonResponse
     */
    protected function fail(
        string $message,
        mixed $errors = null,
        int $statusCode = 400
    ): JsonResponse {
        $response = [
            'status' => 'fail',
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return an error JSON response (server error)
     * 
     * Use for exceptions, server crashes, database errors, etc.
     *
     * @param string $message Error message
     * @param int $statusCode HTTP status code (5xx)
     * @param \Throwable|null $exception Exception object (for debugging)
     * @return JsonResponse
     */
    protected function error(
        string $message,
        int $statusCode = 500,
        ?\Throwable $exception = null
    ): JsonResponse {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        // Include debug information in development environment
        if ($exception && config('app.debug')) {
            $response['debug'] = [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => collect($exception->getTrace())->take(5)->toArray(),
            ];
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a validation error response (422)
     *
     * @param array<string, mixed> $errors Validation errors
     * @param string $message Error message
     * @return JsonResponse
     */
    protected function validationFail(
        array $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return $this->fail($message, $errors, 422);
    }

    /**
     * Return a not found error response (404)
     *
     * @param string $message Error message
     * @return JsonResponse
     */
    protected function notFound(
        string $message = 'Resource not found'
    ): JsonResponse {
        return $this->fail($message, null, 404);
    }

    /**
     * Return an unauthorized error response (401)
     *
     * @param string $message Error message
     * @return JsonResponse
     */
    protected function unauthorized(
        string $message = 'Unauthorized access'
    ): JsonResponse {
        return $this->fail($message, null, 401);
    }

    /**
     * Return a forbidden error response (403)
     *
     * @param string $message Error message
     * @return JsonResponse
     */
    protected function forbidden(
        string $message = 'Access forbidden'
    ): JsonResponse {
        return $this->fail($message, null, 403);
    }

    /**
     * Return a redirect response with success message
     *
     * @param string $route Route name
     * @param string $message Success message
     * @return RedirectResponse
     */
    protected function redirectWithSuccess(
        string $route,
        string $message = 'Operation successful'
    ): RedirectResponse {
        return redirect()->route($route)->with('success', $message);
    }

    /**
     * Return a redirect response with error message
     *
     * @param string $route Route name
     * @param string $message Error message
     * @return RedirectResponse
     */
    protected function redirectWithError(
        string $route,
        string $message = 'Operation failed'
    ): RedirectResponse {
        return redirect()->route($route)->with('error', $message);
    }
}
