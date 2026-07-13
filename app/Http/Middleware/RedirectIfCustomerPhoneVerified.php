<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfCustomerPhoneVerified
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $phone = $request->session()->get('customer_portal.verified_phone');
        $verifiedUntil = $request->session()->get('customer_portal.verified_until');

        if (is_string($phone) && $phone !== '' && is_int($verifiedUntil) && now()->timestamp < $verifiedUntil) {
            return to_route('customer-portal.index');
        }

        return $next($request);
    }
}
