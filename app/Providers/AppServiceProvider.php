<?php

namespace App\Providers;

use App\Contracts\OlxParserInterface;
use App\Services\JsonOlxParser;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(OlxParserInterface::class, JsonOlxParser::class);
    }
}
