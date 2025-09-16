<?php


namespace Arrowpay\ArrowBaze;


use Illuminate\Support\ServiceProvider;


class ArrowBazeServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Publish config
        $this->publishes([
        __DIR__.'/../config/arrowbaze.php' => config_path('arrowbaze.php'),
        ], 'config');


        // Publish migration
        if (! class_exists('CreateArrowbazeTokensTable')) {
        $this->publishes([
        __DIR__.'/../database/migrations/2025_01_01_000000_create_arrowbaze_tokens_table.php' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_arrowbaze_tokens_table.php'),
        ], 'migrations');
        }


        // Publish views
        $this->loadViewsFrom(__DIR__.'/../publishable/views', 'arrowbaze');
        $this->publishes([
        __DIR__.'/../publishable/views' => resource_path('views/vendor/arrowbaze')
        ], 'views');


        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }


    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/arrowbaze.php', 'arrowbaze');


        $this->app->singleton('arrowbaze', function ($app) {
        return new ArrowBaze();
        });
    }
}