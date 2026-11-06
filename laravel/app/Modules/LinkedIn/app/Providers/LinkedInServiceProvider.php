<?php

namespace App\Modules\LinkedIn\app\Providers;

use App\Shared\Providers\BaseModuleServiceProvider;

class LinkedInServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'linkedin';
    }

    protected function getModulePath(): string
    {
        return dirname(__DIR__, 2);
    }
}
