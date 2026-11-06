<?php

namespace App\Modules\Email\app\Providers;

use App\Shared\Providers\BaseModuleServiceProvider;

class EmailServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'email';
    }

    protected function getModulePath(): string
    {
        return dirname(__DIR__, 2);
    }
}
