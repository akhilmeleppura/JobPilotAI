<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        if ($this->app->runningInConsole()) {
            try {
                \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
            } catch (\Throwable $e) {}
        }
    }
}
