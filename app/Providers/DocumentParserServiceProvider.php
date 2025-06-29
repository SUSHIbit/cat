<?php

namespace App\Providers;

use App\Services\DocumentParser;
use Illuminate\Support\ServiceProvider;

class DocumentParserServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(DocumentParser::class, function ($app) {
            return new DocumentParser();
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