<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Broadcast;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Enable broadcasting routes
        Broadcast::routes();

        // Register broadcasting channels
        $this->registerBroadcastChannels();
    }

    /**
     * Register broadcasting channels
     */
    protected function registerBroadcastChannels(): void
    {
        // Public channels untuk SCADA data
        Broadcast::channel('scada-data', function ($user) {
            return true; // Public channel
        });

        Broadcast::channel('scada-realtime', function ($user) {
            return true; // Public channel
        });

        Broadcast::channel('scada-batch', function ($user) {
            return true; // Public channel
        });

        Broadcast::channel('scada-aggregated', function ($user) {
            return true; // Public channel
        });
    }
}
