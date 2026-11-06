<?php

namespace App\Modules\Resume\app\Providers;

use App\Shared\Providers\BaseModuleServiceProvider;

class ResumeServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'resume';
    }

    protected function getModulePath(): string
    {
        return dirname(__DIR__, 2);
    }
}
