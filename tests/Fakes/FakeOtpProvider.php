<?php

namespace Tests\Fakes;

use App\Domain\CustomerPortal\Contracts\OtpProvider;

class FakeOtpProvider implements OtpProvider
{
    /** @var list<array{phone: string, code: string}> */
    private array $messages = [];

    public function send(string $normalizedPhone, string $code): void
    {
        $this->messages[] = [
            'phone' => $normalizedPhone,
            'code' => $code,
        ];
    }

    public function latestCodeFor(string $normalizedPhone): string
    {
        foreach (array_reverse($this->messages) as $message) {
            if ($message['phone'] === $normalizedPhone) {
                return $message['code'];
            }
        }

        throw new \RuntimeException("No OTP was sent to {$normalizedPhone}.");
    }
}
