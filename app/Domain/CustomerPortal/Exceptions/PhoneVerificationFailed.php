<?php

namespace App\Domain\CustomerPortal\Exceptions;

use RuntimeException;

class PhoneVerificationFailed extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The verification code is invalid or expired.');
    }
}
