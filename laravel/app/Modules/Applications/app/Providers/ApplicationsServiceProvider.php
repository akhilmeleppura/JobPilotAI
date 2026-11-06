<?php

namespace App\Modules\Applications\app\Providers;

use App\Shared\Providers\BaseModuleServiceProvider;

class ApplicationsServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'applications';
    }

    protected function getModulePath(): string
    {
        return dirname(__DIR__, 2);
    }
}
