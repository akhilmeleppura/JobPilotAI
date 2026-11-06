<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ModulesServiceProvider extends ServiceProvider
{
    /**
     * List of core domain modules.
     */
    protected array $modules = [
        'Identity',
        'Dashboard',
        'Candidate',
        'RoleProfiles',
        'Resume',
        'Jobs',
        'Applications',
        'Email',
        'AI',
        'Integrations',
        'ImportExport',
        'Archives',
        'LinkedIn',
        'ContentStudio',
        'Automation',
        'Monitoring',
        'Settings',
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        foreach ($this->modules as $module) {
            $providerClass = "App\\Modules\\{$module}\\app\\Providers\\{$module}ServiceProvider";
            if (class_exists($providerClass)) {
                $this->app->register($providerClass);
            }
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Global cross-module bindings and observers if needed
    }
}

