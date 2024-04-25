<?php

namespace App\Providers;

use App\Contracts\OlxParser;
use App\Services\JsonParser;
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
        $this->app->singleton(OlxParser::class, JsonParser::class);
    }
}
