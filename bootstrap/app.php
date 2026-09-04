<?php

use App\Http\Middleware\EnsureEmailIsVerifiedIfRequired;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ThrottleRegistration;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            ThrottleRegistration::class,
        ]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'verified' => EnsureEmailIsVerifiedIfRequired::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->respond(function (Response $response, Throwable $e, Request $request): Response {
            $status = $response->getStatusCode();

            if (in_array($status, [403, 404, 500], true) && ! $request->expectsJson() && ($status !== 500 || $request->user() !== null)) {
                $props = ['status' => $status];
                if ($status === 500) {
                    $user = $request->user();
                    $props += [
                        'title' => __('Unable to complete this information'),
                        'message' => __('A linked record may be missing or inconsistent. Check the athlete or coach delegation, school, municipality, sport, event entry, and team membership. ICT or System Admin can inspect the exact problems in Data Repair.'),
                        'canRepair' => $user !== null && ($user->isAdmin()
                            || $user->hasRole(\App\Enums\UserRole::TournamentICT)
                            || $user->canManageProductionAccounts()),
                    ];
                }

                return Inertia::render('error', $props)
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();
