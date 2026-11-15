<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Exceptions\InvalidCredentials;
use App\Exceptions\RegistrationConflict;
use App\Http\Middleware\PrepareApiRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class ApiErrorResponse
{
    /**
     * Convert framework failures into the public API error contract.
     */
    public static function fromException(Throwable $exception, Request $request): JsonResponse
    {
        if ($exception instanceof ValidationException) {
            return self::make(
                request: $request,
                code: 'VALIDATION_ERROR',
                message: 'The request contains invalid fields.',
                status: 422,
                fields: $exception->errors(),
            );
        }

        if ($exception instanceof AuthenticationException) {
            return self::make($request, 'UNAUTHENTICATED', 'Authentication is required.', 401);
        }

        if ($exception instanceof InvalidCredentials) {
            return self::make(
                request: $request,
                status: 401,
                code: 'INVALID_CREDENTIALS',
                message: $exception->getMessage(),
            );
        }

        if ($exception instanceof RegistrationConflict) {
            return self::make($request, $exception->apiCode, $exception->getMessage(), 409);
        }

        $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
        [$code, $message] = self::errorForStatus($status);
        $headers = $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : [];

        return self::make($request, $code, $message, $status, headers: $headers);
    }

    /**
     * @param  array<string, array<int, string>>|null  $fields
     * @param  array<string, string|string[]>  $headers
     */
    public static function make(
        Request $request,
        string $code,
        string $message,
        int $status,
        ?array $fields = null,
        array $headers = [],
    ): JsonResponse {
        $requestId = $request->attributes->get(PrepareApiRequest::REQUEST_ID_ATTRIBUTE);

        if (! is_string($requestId) || $requestId === '') {
            $requestId = (string) Str::ulid();
            $request->attributes->set(PrepareApiRequest::REQUEST_ID_ATTRIBUTE, $requestId);
        }

        $error = [
            'code' => $code,
            'message' => $message,
            'request_id' => $requestId,
        ];

        if ($fields !== null) {
            $error['fields'] = $fields;
        }

        $headers['X-Request-ID'] = $requestId;

        return response()->json(['error' => $error], $status, $headers);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function errorForStatus(int $status): array
    {
        return match ($status) {
            400 => ['BAD_REQUEST', 'The request could not be understood.'],
            401 => ['UNAUTHENTICATED', 'Authentication is required.'],
            403 => ['FORBIDDEN', 'You are not allowed to perform this action.'],
            404 => ['NOT_FOUND', 'The requested resource was not found.'],
            405 => ['METHOD_NOT_ALLOWED', 'The request method is not allowed for this resource.'],
            406 => ['NOT_ACCEPTABLE', 'The requested response format is not supported.'],
            409 => ['CONFLICT', 'The request conflicts with the current resource state.'],
            415 => ['UNSUPPORTED_MEDIA_TYPE', 'The request content type is not supported.'],
            422 => ['UNPROCESSABLE_CONTENT', 'The request could not be processed.'],
            429 => ['TOO_MANY_REQUESTS', 'Too many requests were made.'],
            500 => ['INTERNAL_SERVER_ERROR', 'An unexpected server error occurred.'],
            503 => ['SERVICE_UNAVAILABLE', 'The service is temporarily unavailable.'],
            default => ['HTTP_ERROR', 'The request could not be completed.'],
        };
    }
}
