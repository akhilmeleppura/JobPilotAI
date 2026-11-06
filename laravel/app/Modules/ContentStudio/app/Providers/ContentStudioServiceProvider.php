<?php

namespace App\Modules\ContentStudio\app\Providers;

use App\Shared\Providers\BaseModuleServiceProvider;

class ContentStudioServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'contentstudio';
    }

    protected function getModulePath(): string
    {
        return dirname(__DIR__, 2);
    }
}
