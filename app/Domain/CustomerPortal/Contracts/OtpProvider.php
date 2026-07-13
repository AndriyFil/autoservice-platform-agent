<?php

namespace App\Domain\CustomerPortal\Contracts;

interface OtpProvider
{
    public function send(string $normalizedPhone, string $code): void;
}
