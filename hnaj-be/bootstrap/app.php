<?php

use App\Http\Responses\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($exception instanceof ValidationException) {
                return ApiResponse::error(
                    message: 'The given data was invalid.',
                    errors: $exception->errors(),
                    code: 'VALIDATION_ERROR',
                    status: 422,
                );
            }

            if ($exception instanceof NotFoundHttpException) {
                return ApiResponse::error(
                    message: 'The requested resource was not found.',
                    code: 'NOT_FOUND',
                    status: 404,
                );
            }

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
        });
    })->create();
