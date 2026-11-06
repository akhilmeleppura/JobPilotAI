<?php

namespace App\Modules\Dashboard\app\Providers;

use App\Shared\Providers\BaseModuleServiceProvider;

class DashboardServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'dashboard';
    }

    protected function getModulePath(): string
    {
        return dirname(__DIR__, 2);
    }
}

