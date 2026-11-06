<?php

namespace App\Modules\Monitoring\app\Providers;

use App\Shared\Providers\BaseModuleServiceProvider;

class MonitoringServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'monitoring';
    }

    protected function getModulePath(): string
    {
        return dirname(__DIR__, 2);
    }

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Modules\Monitoring\app\Console\Commands\BackupDatabaseCommand::class,
            ]);
        }
    }
}
