<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/adminCustomerRoute.php'));
            Route::middleware('web')
                ->group(base_path('routes/customerRoute.php'));
            Route::middleware('web')
                ->group(base_path('routes/staffRoute.php'));
            Route::middleware('web')
                ->group(base_path('routes/roleRoute.php'));
            Route::middleware('web')
                ->group(base_path('routes/dashboardRoute.php'));
            Route::middleware('web')
                ->group(base_path('routes/productRoute.php'));
            Route::middleware('web')
                ->group(base_path('routes/goldPriceRoute.php'));
            Route::middleware('web')
                ->group(base_path('routes/inventoryRoute.php'));
            Route::middleware('web')
                ->group(base_path('routes/kycRoute.php'));
            Route::middleware('web')
                ->group(base_path('routes/emiPlanRoute.php'));
            Route::middleware('web')
                ->group(base_path('routes/purchasePreviewRoute.php'));
            Route::middleware('web')
                ->group(base_path('routes/emiCalculatorRoute.php'));
            Route::middleware('web')
                ->group(base_path('routes/bookingRoute.php'));
            Route::middleware('web')
                ->group(base_path('routes/emiScheduleRoute.php'));
            Route::middleware('web')
                ->group(base_path('routes/paymentRoute.php'));
            Route::middleware('web')
                ->group(base_path('routes/receiptRoute.php'));
            Route::middleware('web')
                ->group(base_path('routes/invoiceRoute.php'));
            Route::middleware('web')
                ->group(base_path('routes/deliveryRoute.php'));
            Route::middleware('web')
                ->group(base_path('routes/referralRoute.php'));
            Route::middleware('web')
                ->group(base_path('routes/sellOldGoldRoute.php'));
            Route::middleware('web')
                ->group(base_path('routes/franchiseRoute.php'));
            Route::middleware('web')
                ->group(base_path('routes/reportRoute.php'));
            Route::middleware('web')
                ->group(base_path('routes/auditRoute.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'customer' => \App\Http\Middleware\EnsureCustomer::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\RedirectCustomersFromAdmin::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'payment/cashfree/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
