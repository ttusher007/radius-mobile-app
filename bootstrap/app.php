<?php

use App\Exceptions\InsufficientPermissionException;
use App\Http\Middleware\EnsureRoutePermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'route.permission' => EnsureRoutePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (InsufficientPermissionException $e, Request $request) {
            if ($request->expectsJson()) {
                $data = $e->toViewData();

                return response()->json([
                    'message' => $e->getMessage(),
                    'page' => $data['pageLabel'],
                    'required_permissions' => collect($data['missingPermissions'])->pluck('name')->all(),
                    'missing_scope' => $data['missingScope'],
                    'scope_permissions' => collect($data['scopePermissions'])->pluck('name')->all(),
                ], 403);
            }

            return response()->view('errors.403', $e->toViewData(), 403);
        });
    })->create();
