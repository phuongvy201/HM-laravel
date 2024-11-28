<?php

namespace App\Providers;

use App\Services\USPSService;
use Illuminate\Support\ServiceProvider;

class USPSServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(USPSService::class, function ($app) {
            return new USPSService(
                config('services.usps.api_url'),
                config('services.usps.client_id'),
                config('services.usps.client_secret'),
                config('services.usps.access_token')
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
