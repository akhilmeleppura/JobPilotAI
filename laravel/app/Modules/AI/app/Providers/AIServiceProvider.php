<?php

namespace App\Modules\AI\app\Providers;

use App\Shared\Providers\BaseModuleServiceProvider;

class AIServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'ai';
    }

    protected function getModulePath(): string
    {
        return dirname(__DIR__, 2);
    }
}
