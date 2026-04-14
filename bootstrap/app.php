<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'tier' => \App\Http\Middleware\CheckTierAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // For non-GET Inertia requests (form submissions and mutations), redirect back
        // with a flash error rather than returning a raw HTML error page that Inertia
        // cannot display gracefully inside the SPA shell.

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->header('X-Inertia') && ! $request->isMethod('GET')) {
                return back()->with('error', 'You are not authorized to perform this action.');
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->header('X-Inertia') && ! $request->isMethod('GET')) {
                return back()->with('error', 'The requested resource could not be found.');
            }
        });
    })->create();
