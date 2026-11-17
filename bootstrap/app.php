<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\SetUtf8Encoding;
use App\Http\Middleware\SuperAdminContextMiddleware;
use App\Http\Middleware\ValidateModuleAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->encryptCookies(except: [
            'appearance',
            'sidebar_state',
        ]);

        $middleware->web([
            SetUtf8Encoding::class, // <-- Force UTF-8 encoding
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            IdentifyTenant::class, // <-- Multi-tenant middleware
            SuperAdminContextMiddleware::class, // <-- SuperAdmin context handling
        ]);

        // El tenant debe fijarse antes del route-model-binding para que los
        // modelos inyectados en rutas queden filtrados por tenant.
        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: IdentifyTenant::class,
        );

        $middleware->alias([
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
            'module' => ValidateModuleAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Token CSRF caducado (419) en una petición Inertia: sin esto el formulario
        // no hace nada y no muestra error. Volver a la página trae un token nuevo,
        // así que el siguiente intento funciona.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if ($response->getStatusCode() !== 419 || ! $request->header('X-Inertia')) {
                return $response;
            }

            $message = 'Tu sesión expiró. Vuelve a intentarlo.';
            $key = $request->is('login', 'forgot-password', 'reset-password', 'two-factor-challenge', 'user/confirm-password')
                ? 'email'
                : 'csrf';

            return back()->withErrors([$key => $message])->withInput($request->except('password', 'password_confirmation', 'current_password'));
        });
    })->create();
