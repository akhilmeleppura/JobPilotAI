<?php

namespace App\Modules\Archives\app\Providers;

use App\Shared\Providers\BaseModuleServiceProvider;

class ArchivesServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'archives';
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
                \App\Modules\Archives\app\Console\Commands\ArchiveMonthlyApplicationsCommand::class,
            ]);
        }
    }
}
