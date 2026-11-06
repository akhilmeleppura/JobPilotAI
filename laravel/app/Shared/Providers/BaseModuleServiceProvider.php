<?php

namespace App\Shared\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

abstract class BaseModuleServiceProvider extends ServiceProvider
{
    /**
     * Unique module identifier in kebab-case (e.g. 'identity', 'dashboard', 'jobs').
     */
    abstract protected function getModuleName(): string;

    /**
     * Absolute path to module root directory.
     */
    abstract protected function getModulePath(): string;

    /**
     * Bootstrap module services.
     */
    public function boot(): void
    {
        $this->registerModuleRoutes();
        $this->registerModuleViews();
        $this->registerModuleMigrations();
    }

    /**
     * Register module specific routes.
     */
    protected function registerModuleRoutes(): void
    {
        $modulePath = $this->getModulePath();
        $webRoutes = $modulePath . '/routes/web.php';
        $apiRoutes = $modulePath . '/routes/api.php';

        if (file_exists($webRoutes)) {
            Route::middleware('web')
                ->group($webRoutes);
        }

        if (file_exists($apiRoutes)) {
            Route::middleware('api')
                ->prefix('api')
                ->group($apiRoutes);
        }
    }

    /**
     * Register module views with namespace.
     */
    protected function registerModuleViews(): void
    {
        $viewsPath = $this->getModulePath() . '/resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, $this->getModuleName());
        }
    }

    /**
     * Register module migrations.
     */
    protected function registerModuleMigrations(): void
    {
        $migrationsPath = $this->getModulePath() . '/database/migrations';
        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }
}

