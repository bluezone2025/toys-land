<?php

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
        $middleware->appendToGroup('store', [
            \App\Http\Middleware\SetStoreLocale::class,
            \App\Http\Middleware\TrackVisit::class,
            \App\Http\Middleware\CompressResponse::class,
        ]);
        // Outermost, so it also sees the cookies added by the session and encryption layers.
        $middleware->prepend(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->web(
            replace: [\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class => \App\Http\Middleware\PreventRequestForgery::class],
        );
        $middleware->redirectUsersTo(fn () => route('account'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
