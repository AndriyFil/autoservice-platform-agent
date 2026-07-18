<?php

namespace App\Http\Controllers;

use App\Domain\CustomerPortal\Actions\RequestPhoneVerificationAction;
use App\Domain\CustomerPortal\Actions\VerifyPhoneCodeAction;
use App\Domain\CustomerPortal\Exceptions\PhoneVerificationFailed;
use App\Http\Requests\RequestCustomerPortalCodeRequest;
use App\Http\Requests\VerifyCustomerPortalCodeRequest;
use App\Support\Urls\AppUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class CustomerPortalAccessController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('CustomerPortal/RequestAccess', [
            'sessionExpired' => session()->get('customer_portal.session_expired', false),
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'adminLoginUrl' => AppUrl::adminPath('/login'),
            'adminRegisterUrl' => AppUrl::adminPath('/register'),
        ]);
    }

    public function store(
        RequestCustomerPortalCodeRequest $request,
        RequestPhoneVerificationAction $requestVerification,
    ): RedirectResponse {
        $challenge = $requestVerification->handle($request->phone());

        $request->session()->put([
            'customer_portal.pending_challenge_id' => $challenge->id,
            'customer_portal.pending_phone' => $challenge->phone_normalized,
        ]);

        return to_route('customer-portal.verify.create');
    }

    public function verifyCreate(): Response|RedirectResponse
    {
        $phone = session()->get('customer_portal.pending_phone');

        if (! is_string($phone) || $phone === '') {
            return to_route('customer-portal.access.create');
        }

        return Inertia::render('CustomerPortal/VerifyCode', [
            'maskedPhone' => $this->maskPhone($phone),
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'adminLoginUrl' => AppUrl::adminPath('/login'),
            'adminRegisterUrl' => AppUrl::adminPath('/register'),
        ]);
    }

    public function verifyStore(
        VerifyCustomerPortalCodeRequest $request,
        VerifyPhoneCodeAction $verifyCode,
    ): RedirectResponse {
        $challengeId = $request->session()->get('customer_portal.pending_challenge_id');

        try {
            $verifiedPhone = is_int($challengeId)
                ? $verifyCode->handle($challengeId, $request->code())
                : throw new PhoneVerificationFailed;
        } catch (PhoneVerificationFailed $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        $request->session()->regenerate();
        $request->session()->forget([
            'customer_portal.pending_challenge_id',
            'customer_portal.pending_phone',
        ]);
        $request->session()->put([
            'customer_portal.verified_phone' => $verifiedPhone,
            'customer_portal.verified_until' => now()
                ->addMinutes((int) config('customer_portal.verified_session_minutes'))
                ->timestamp,
        ]);

        return to_route('customer-portal.index');
    }

    private function maskPhone(string $phone): string
    {
        return str_repeat('•', max(strlen($phone) - 4, 0)).substr($phone, -4);
    }
}
