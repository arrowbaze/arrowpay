<?php

namespace Arrowpay\ArrowBaze;

use Illuminate\Support\ServiceProvider;
use Arrowpay\ArrowBaze\Helpers\License;

class ArrowBazeServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../config/arrowbaze.php' => config_path('arrowbaze.php'),
        ], 'config');

        // Publish migration if not exists
        if (! class_exists('CreateArrowbazeTokensTable')) {
            $timestamp = date('Y_m_d_His', time());
            $this->publishes([
                __DIR__ . '/../database/migrations/2025_01_01_000000_create_arrowbaze_tokens_table.php' 
                    => database_path("migrations/{$timestamp}_create_arrowbaze_tokens_table.php"),
            ], 'migrations');
        }

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // ✅ Delay license validation until application is fully booted
        $this->app->booted(function () {
            if (config('arrowbaze.license_key')) {
                License::bootFromConfig();
            }
        });
    }

    public function register()
    {
        // Merge package config with app config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/arrowbaze.php',
            'arrowpay'
        );

        // Bind ArrowBaze to the service container
        $this->app->singleton('arrowpay', function ($app) {
            return new ArrowPay();
        });
    }
}
