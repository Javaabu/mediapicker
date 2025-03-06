<?php

namespace Javaabu\Mediapicker;

use Illuminate\Support\ServiceProvider;

class MediapickerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        // declare publishes
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/mediapicker.php' => config_path('mediapicker.php'),
            ], 'mediapicker-config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // merge package config with user defined config
        $this->mergeConfigFrom(__DIR__ . '/../config/mediapicker.php', 'mediapicker');
    }
}
