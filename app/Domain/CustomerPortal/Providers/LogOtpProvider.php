<?php

namespace App\Domain\CustomerPortal\Providers;

use App\Domain\CustomerPortal\Contracts\OtpProvider;
use Illuminate\Support\Facades\Log;

class LogOtpProvider implements OtpProvider
{
    public function send(string $normalizedPhone, string $code): void
    {
        Log::info('Customer Portal verification code', [
            'phone' => $normalizedPhone,
            'code' => $code,
        ]);
    }
}
