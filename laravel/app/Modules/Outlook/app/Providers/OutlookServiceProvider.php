<?php

namespace App\Modules\Outlook\app\Providers;

use App\Shared\Providers\BaseModuleServiceProvider;

class OutlookServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'outlook';
    }

    protected function getModulePath(): string
    {
        return dirname(__DIR__, 2);
    }
}
