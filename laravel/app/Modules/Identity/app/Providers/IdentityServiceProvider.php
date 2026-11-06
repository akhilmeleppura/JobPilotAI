<?php

namespace App\Modules\Identity\app\Providers;

use App\Shared\Providers\BaseModuleServiceProvider;

class IdentityServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'identity';
    }

    protected function getModulePath(): string
    {
        return dirname(__DIR__, 2);
    }
}

