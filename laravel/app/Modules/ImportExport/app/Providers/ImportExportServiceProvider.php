<?php

namespace App\Modules\ImportExport\app\Providers;

use App\Shared\Providers\BaseModuleServiceProvider;

class ImportExportServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'importexport';
    }

    protected function getModulePath(): string
    {
        return dirname(__DIR__, 2);
    }
}
