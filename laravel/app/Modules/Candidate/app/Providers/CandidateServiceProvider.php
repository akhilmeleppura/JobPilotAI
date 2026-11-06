<?php

namespace App\Modules\Candidate\app\Providers;

use App\Shared\Providers\BaseModuleServiceProvider;

class CandidateServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'candidate';
    }

    protected function getModulePath(): string
    {
        return dirname(__DIR__, 2);
    }
}
