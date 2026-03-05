<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // CORS - Allow cross-origin requests from Swagger UI and frontend
        $middleware->api(prepend: [
            HandleCors::class,
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);

        $middleware->alias([
            'super_admin'     => \App\Http\Middleware\SuperAdminMiddleware::class,
            'org_admin'       => \App\Http\Middleware\OrganizationAdminMiddleware::class,
            'super_admin_web' => \App\Http\Middleware\SuperAdminWebMiddleware::class,
            'org_admin_web'   => \App\Http\Middleware\OrgAdminWebMiddleware::class,
            'permission'      => \App\Http\Middleware\CheckPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Force JSON response for all API routes (fixes HTML 401/404/500 responses)
        $exceptions->shouldRenderJsonWhen(function (Request $request, \Throwable $e) {
            return $request->is('api/*');
        });

        // Handle JWT token exceptions
        $exceptions->render(function (\Tymon\JWTAuth\Exceptions\JWTException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Token is invalid or expired',
                    'error'   => $e->getMessage(),
                ], 401);
            }
        });

        // Handle token not provided
        $exceptions->render(function (\Tymon\JWTAuth\Exceptions\TokenBlacklistedException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Token has been blacklisted',
                    'error'   => $e->getMessage(),
                ], 401);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Unauthenticated - Missing or invalid Bearer token',
                    'error'   => 'Please provide a valid JWT token in Authorization header: Bearer YOUR_TOKEN',
                    'hint'    => 'Call POST /api/v1/auth/login first to get a token',
                ], 401);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Not found.'], 404);
            }
        });
    })->create();
