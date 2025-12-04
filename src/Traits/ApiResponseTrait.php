<?php

namespace LaravelArchitect\Traits;

use Illuminate\Http\JsonResponse;
use LaravelArchitect\Console\Commands\Support\ConfigHelper;

trait ApiResponseTrait
{
    /**
     * Return a success response.
     */
    protected function successResponse(mixed $data, ?string $message = null, ?int $statusCode = null): JsonResponse
    {
        $statusCode = $statusCode ?? ConfigHelper::statusCode('success');

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
        $message = $message ?? ConfigHelper::message('created');

        return response()->json([
            'data' => $data,
            'message' => $message
        ], ConfigHelper::statusCode('created'));
    }

    /**
     * Return an error response.
     */
    protected function errorResponse(string $message, ?int $statusCode = null, ?array $errors = null): JsonResponse
    {
        $statusCode = $statusCode ?? ConfigHelper::statusCode('server_error');

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
        $message = $message ?? ConfigHelper::message('not_found');

        return response()->json([
            'message' => $message
        ], ConfigHelper::statusCode('not_found'));
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

        return response()->json($response, ConfigHelper::statusCode('no_content'));
    }

    /**
     * Return a validation error response.
     */
    protected function validationErrorResponse(array $errors, ?string $message = null): JsonResponse
    {
        $message = $message ?? ConfigHelper::message('validation_error');

        return response()->json([
            'message' => $message,
            'errors' => $errors
        ], ConfigHelper::statusCode('unprocessable_entity'));
    }

    /**
     * Return an unauthorized response.
     */
    protected function unauthorizedResponse(?string $message = null): JsonResponse
    {
        $message = $message ?? ConfigHelper::message('unauthorized');

        return response()->json([
            'message' => $message
        ], ConfigHelper::statusCode('unauthorized'));
    }

    /**
     * Return a forbidden response.
     */
    protected function forbiddenResponse(?string $message = null): JsonResponse
    {
        $message = $message ?? ConfigHelper::message('forbidden');

        return response()->json([
            'message' => $message
        ], ConfigHelper::statusCode('forbidden'));
    }
}
