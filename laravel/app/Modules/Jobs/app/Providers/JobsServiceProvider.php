<?php

namespace App\Modules\Jobs\app\Providers;

use App\Shared\Providers\BaseModuleServiceProvider;

class JobsServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'jobs';
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
                \App\Modules\Jobs\app\Console\Commands\FetchJobsCommand::class,
            ]);
        }
    }
}
