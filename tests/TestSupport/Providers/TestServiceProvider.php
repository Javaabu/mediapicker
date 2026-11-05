<?php

namespace Javaabu\Mediapicker\Tests\TestSupport\Providers;

use Illuminate\Support\ServiceProvider;

class TestServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database');
    }
}
