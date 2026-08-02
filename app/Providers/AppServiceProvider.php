<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind('dompdf.wrapper', function ($app) {
            return new \App\Services\CustomPDF(
                $app['dompdf'],
                $app['config'],
                $app['files'],
                $app['view']
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
