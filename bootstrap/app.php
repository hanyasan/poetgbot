<?php

use App\Jobs\PriceCheck;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('hook')
                ->prefix('hook')
                ->group(base_path('routes/hook.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // RateLimiter::for('api') is defined in AppServiceProvider::boot().
        $middleware->throttleApi();

        // The "hook" group mirrors "api": throttled and bound, but unauthenticated.
        $middleware->group('hook', [
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->trustProxies(headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
            | Request::HEADER_X_FORWARDED_AWS_ELB);

        $middleware->trimStrings(except: [
            'current_password',
            'password',
            'password_confirmation',
        ]);

        $middleware->redirectGuestsTo('/home');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        if (config('app.env') === 'local') {
            $exceptions->shouldRenderJsonWhen(
                fn (Request $request) => $request->is('api/*') || $request->expectsJson()
            );

            return;
        }

        $exceptions->render(function (NotFoundHttpException $exception) {
            return response()->json([
                'success' => false,
                'data' => 'Not found',
            ], 404);
        });

        $exceptions->render(function (\Throwable $throwable) {
            return response()->json([
                'success' => false,
                'data' => 'Server error',
            ], 500);
        });
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->job(PriceCheck::class)->everyTenMinutes();
    })
    ->create();
