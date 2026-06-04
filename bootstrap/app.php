<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'permission'   => \App\Http\Middleware\CheckPermission::class,
            'branch.scope' => \App\Http\Middleware\BranchScopeMiddleware::class,
            'audit'        => \App\Http\Middleware\AuditLogger::class,
        ]);

        // Automatically audit all mutating staff requests
        $middleware->appendToGroup('web', \App\Http\Middleware\AuditLogger::class);

        // When an already-authenticated customer hits a guest:customers route,
        // send them to the customer dashboard — NOT the staff login page.
        $middleware->redirectUsersTo(function (Request $request) {
            if (Auth::guard('customers')->check()) {
                return route('customer.dashboard');
            }
            // Staff or default auth guard — send to staff dashboard
            return route('staff.dashboard');
        });
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
