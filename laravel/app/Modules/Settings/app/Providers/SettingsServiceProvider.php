<?php

namespace App\Modules\Settings\app\Providers;

use App\Shared\Providers\BaseModuleServiceProvider;

class SettingsServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'settings';
    }

    protected function getModulePath(): string
    {
        return dirname(__DIR__, 2);
    }
}
