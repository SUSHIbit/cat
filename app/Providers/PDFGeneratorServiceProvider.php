<?php

namespace App\Providers;

use App\Services\PDFGenerator;
use Illuminate\Support\ServiceProvider;

class PDFGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(PDFGenerator::class, function ($app) {
            return new PDFGenerator();
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