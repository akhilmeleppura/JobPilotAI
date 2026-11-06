<?php

namespace App\Modules\Integrations\app\Providers;

use App\Shared\Providers\BaseModuleServiceProvider;

class IntegrationsServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'integrations';
    }

    protected function getModulePath(): string
    {
        return dirname(__DIR__, 2);
    }
}
