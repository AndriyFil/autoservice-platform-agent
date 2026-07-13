<?php

namespace App\Domain\CustomerPortal\Actions;

use App\Domain\CustomerPortal\Exceptions\PhoneVerificationFailed;
use App\Models\CustomerPhoneVerification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class VerifyPhoneCodeAction
{
    public function handle(int $challengeId, string $code): string
    {
        $verifiedPhone = DB::transaction(function () use ($challengeId, $code): ?string {
            $challenge = CustomerPhoneVerification::query()
                ->lockForUpdate()
                ->find($challengeId);

            if ($challenge === null || ! $this->isUsable($challenge)) {
                return null;
            }

            if (! Hash::check($code, $challenge->code_hash)) {
                $challenge->attempts++;

                if ($challenge->attempts >= (int) config('customer_portal.max_verification_attempts')) {
                    $challenge->invalidated_at = now();
                }

                $challenge->save();

                return null;
            }

            $challenge->consumed_at = now();
            $challenge->save();

            return $challenge->phone_normalized;
        });

        if ($verifiedPhone === null) {
            throw new PhoneVerificationFailed;
        }

        return $verifiedPhone;
    }

    private function isUsable(CustomerPhoneVerification $challenge): bool
    {
        return $challenge->consumed_at === null
            && $challenge->invalidated_at === null
            && $challenge->expires_at->isFuture()
            && $challenge->attempts < (int) config('customer_portal.max_verification_attempts');
    }
}
