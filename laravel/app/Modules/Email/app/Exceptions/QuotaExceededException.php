<?php

namespace App\Modules\Email\app\Exceptions;

use RuntimeException;

class QuotaExceededException extends RuntimeException
{
    public function __construct(string $message = 'Daily email outreach quota ceiling reached.')
    {
        parent::__construct($message);
    }
}

