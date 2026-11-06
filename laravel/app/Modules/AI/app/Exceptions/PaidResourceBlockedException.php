<?php

namespace App\Modules\AI\app\Exceptions;

use RuntimeException;

class PaidResourceBlockedException extends RuntimeException
{
    public function __construct(string $message = 'Access to paid AI resources is strictly blocked by the FREE_RESOURCES_ONLY policy.')
    {
        parent::__construct($message);
    }
}

