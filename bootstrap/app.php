<?php

use App\Exceptions\DomainException;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SetLocale;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'locale' => SetLocale::class,
            'active' => EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (DomainException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => $exception->getMessage(),
                'error' => $exception->errorCode(),
            ], $exception->status());
        });

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->expectsJson()
                || config('app.debug')
                || $exception instanceof AuthenticationException
                || $exception instanceof ModelNotFoundException
                || $exception instanceof ValidationException
                || $exception instanceof HttpExceptionInterface) {
                return null;
            }

            return response()->json([
                'message' => 'An unexpected error occurred.',
                'error' => 'internal_error',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        });
    })->create();
