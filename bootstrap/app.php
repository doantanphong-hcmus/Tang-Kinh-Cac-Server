<?php

declare(strict_types=1);

use App\Http\Middleware\PrepareApiRequest;
use App\Http\Responses\ApiErrorResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(PrepareApiRequest::class);
        $middleware->remove(HandleCors::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->context(function (): array {
            $request = request();

            if (! $request->is('api/*')) {
                return [];
            }

            return [
                'request_id' => $request->attributes->get(PrepareApiRequest::REQUEST_ID_ATTRIBUTE),
                'route' => $request->path(),
                'status' => 500,
                'user_id' => $request->user()?->getAuthIdentifier(),
            ];
        });

        $exceptions->render(function (Throwable $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiErrorResponse::fromException($exception, $request);
        });
    })->create();
