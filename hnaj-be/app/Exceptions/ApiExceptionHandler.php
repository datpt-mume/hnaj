<?php

namespace App\Exceptions;

use App\Enums\AuthErrorCode;
use App\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Maps exceptions to the shared API error envelope for /api requests.
 */
final class ApiExceptionHandler
{
    public function render(Throwable $exception): JsonResponse
    {
        return match (true) {
            $exception instanceof AuthFlowException => $this->renderAuthFlow($exception),
            $exception instanceof AuthenticationException => $this->renderUnauthenticated(),
            $exception instanceof ValidationException => $this->renderValidation($exception),
            $exception instanceof NotFoundHttpException => $this->renderNotFound(),
            default => $this->renderFallback($exception),
        };
    }

    private function renderAuthFlow(AuthFlowException $exception): JsonResponse
    {
        return ApiResponse::error(
            message: $exception->getMessage(),
            code: $exception->errorCode->value,
            status: $exception->status,
        );
    }

    private function renderUnauthenticated(): JsonResponse
    {
        return ApiResponse::error(
            message: 'Authentication is required to access this resource.',
            code: AuthErrorCode::Unauthenticated->value,
            status: 401,
        );
    }

    private function renderValidation(ValidationException $exception): JsonResponse
    {
        return ApiResponse::error(
            message: 'The given data was invalid.',
            errors: $exception->errors(),
            code: 'VALIDATION_ERROR',
            status: 422,
        );
    }

    private function renderNotFound(): JsonResponse
    {
        return ApiResponse::error(
            message: 'The requested resource was not found.',
            code: 'NOT_FOUND',
            status: 404,
        );
    }

    private function renderFallback(Throwable $exception): JsonResponse
    {
        $status = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : 500;

        return ApiResponse::error(
            message: $status >= 500
                ? 'An unexpected error occurred.'
                : ($exception->getMessage() ?: 'The request could not be completed.'),
            code: $status >= 500 ? 'INTERNAL_SERVER_ERROR' : 'HTTP_ERROR',
            status: $status,
        );
    }
}
