<?php

namespace App\Domain\CustomerPortal\Actions;

use App\Domain\CustomerPortal\Contracts\OtpProvider;
use App\Domain\Shared\ValueObjects\Phone;
use App\Models\CustomerPhoneVerification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RequestPhoneVerificationAction
{
    public function __construct(
        private readonly OtpProvider $otpProvider,
    ) {}

    public function handle(string $rawPhone): CustomerPhoneVerification
    {
        $normalizedPhone = (new Phone($rawPhone))->normalize();
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $codeHash = Hash::make($code);

        return DB::transaction(function () use ($normalizedPhone, $code, $codeHash): CustomerPhoneVerification {
            DB::select(
                'select pg_advisory_xact_lock(hashtextextended(?, 0))',
                [$normalizedPhone],
            );

            CustomerPhoneVerification::query()
                ->where('phone_normalized', $normalizedPhone)
                ->whereNull('consumed_at')
                ->whereNull('invalidated_at')
                ->where('expires_at', '>', now())
                ->update(['invalidated_at' => now()]);

            $challenge = CustomerPhoneVerification::query()->create([
                'phone_normalized' => $normalizedPhone,
                'code_hash' => $codeHash,
                'expires_at' => now()->addMinutes((int) config('customer_portal.otp_lifetime_minutes')),
                'attempts' => 0,
            ]);

            $this->otpProvider->send($normalizedPhone, $code);

            return $challenge;
        });
    }
}
