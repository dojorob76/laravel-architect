<?php

namespace LaravelArchitect\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    /**
     * Return a success response.
     */
    protected function successResponse(mixed $data, ?string $message = null, ?int $statusCode = null): JsonResponse
    {
        $statusCode = $statusCode ?? config('api_scaffolding.status_codes.success', 200);

        $response = ['data' => $data];

        if ($message) {
            $response['message'] = $message;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a created response.
     */
    protected function createdResponse(mixed $data, ?string $message = null): JsonResponse
    {
        $message = $message ?? config('api_scaffolding.messages.created', 'Resource created successfully.');

        return response()->json([
            'data' => $data,
            'message' => $message
        ], config('api_scaffolding.status_codes.created', 201));
    }

    /**
     * Return an error response.
     */
    protected function errorResponse(string $message, ?int $statusCode = null, ?array $errors = null): JsonResponse
    {
        $statusCode = $statusCode ?? config('api_scaffolding.status_codes.server_error', 500);

        $response = ['message' => $message];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a not found response.
     */
    protected function notFoundResponse(?string $message = null): JsonResponse
    {
        $message = $message ?? config('api_scaffolding.messages.not_found', 'Resource not found.');

        return response()->json([
            'message' => $message
        ], config('api_scaffolding.status_codes.not_found', 404));
    }

    /**
     * Return a no content response.
     */
    protected function noContentResponse(?string $message = null): JsonResponse
    {
        $response = [];

        if ($message) {
            $response['message'] = $message;
        }

        return response()->json($response, config('api_scaffolding.status_codes.no_content', 204));
    }

    /**
     * Return a validation error response.
     */
    protected function validationErrorResponse(array $errors, ?string $message = null): JsonResponse
    {
        $message = $message ?? config('api_scaffolding.messages.validation_error', 'Validation failed.');

        return response()->json([
            'message' => $message,
            'errors' => $errors
        ], config('api_scaffolding.status_codes.unprocessable_entity', 422));
    }

    /**
     * Return an unauthorized response.
     */
    protected function unauthorizedResponse(?string $message = null): JsonResponse
    {
        $message = $message ?? config('api_scaffolding.messages.unauthorized', 'Unauthorized access.');

        return response()->json([
            'message' => $message
        ], config('api_scaffolding.status_codes.unauthorized', 401));
    }

    /**
     * Return a forbidden response.
     */
    protected function forbiddenResponse(?string $message = null): JsonResponse
    {
        $message = $message ?? config('api_scaffolding.messages.forbidden', 'Access forbidden.');

        return response()->json([
            'message' => $message
        ], config('api_scaffolding.status_codes.forbidden', 403));
    }
}
