<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Laravel ignores these two by default (they're "expected" 401/403
        // responses); un-ignore them so the warnings below actually reach
        // the log, per docs/PHASE-1-SPEC.md's logging requirement.
        $exceptions->stopIgnoring([
            AuthenticationException::class,
            AuthorizationException::class,
        ]);

        $exceptions->report(function (AuthenticationException $e) {
            Log::warning('auth.unauthenticated_access', [
                'path' => request()?->path(),
                'ip' => request()?->ip(),
            ]);

            return false;
        });

        $exceptions->report(function (AuthorizationException $e) {
            Log::warning('auth.access_denied', [
                'user_id' => Auth::id(),
                'path' => request()?->path(),
                'ip' => request()?->ip(),
            ]);

            return false;
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
