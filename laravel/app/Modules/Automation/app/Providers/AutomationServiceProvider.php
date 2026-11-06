<?php

namespace App\Modules\Automation\app\Providers;

use App\Shared\Providers\BaseModuleServiceProvider;

class AutomationServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'automation';
    }

    protected function getModulePath(): string
    {
        return dirname(__DIR__, 2);
    }
}
