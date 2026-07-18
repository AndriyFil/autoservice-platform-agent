<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Support\Urls\AppUrl;
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
        $middleware->trimStrings(except: [
            fn ($request) => $request->is('intake') && $request->isMethod('post'),
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->redirectGuestsTo(fn () => AppUrl::adminPathWhenSplit('/login'));
        $middleware->redirectUsersTo(fn () => AppUrl::adminPathWhenSplit('/dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
