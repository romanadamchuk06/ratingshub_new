<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (\Illuminate\Http\Response $response) {
            if ($response->getStatusCode() === 404) {
                return \Inertia\Inertia::render('errors/404')
                    ->toResponse(request())
                    ->setStatusCode(404);
            }

            if ($response->getStatusCode() === 500) {
                return \Inertia\Inertia::render('errors/500')
                    ->toResponse(request())
                    ->setStatusCode(500);
            }

            if ($response->getStatusCode() === 503) {
                return \Inertia\Inertia::render('errors/503')
                    ->toResponse(request())
                    ->setStatusCode(503);
            }

            return $response;
        });
    })->create();
