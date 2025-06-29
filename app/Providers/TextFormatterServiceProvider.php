<?php

namespace App\Providers;

use App\Services\TextFormatter;
use Illuminate\Support\ServiceProvider;

class TextFormatterServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(TextFormatter::class, function ($app) {
            return new TextFormatter();
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