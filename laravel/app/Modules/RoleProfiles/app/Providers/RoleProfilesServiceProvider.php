<?php

namespace App\Modules\RoleProfiles\app\Providers;

use App\Shared\Providers\BaseModuleServiceProvider;

class RoleProfilesServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'roleprofiles';
    }

    protected function getModulePath(): string
    {
        return dirname(__DIR__, 2);
    }
}
