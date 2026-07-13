<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVerifiedCustomerPhone
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $phone = $request->session()->get('customer_portal.verified_phone');
        $verifiedUntil = $request->session()->get('customer_portal.verified_until');

        if (is_string($phone) && $phone !== '' && is_int($verifiedUntil) && now()->timestamp < $verifiedUntil) {
            return $next($request);
        }

        $sessionExpired = $request->session()->has('customer_portal.verified_phone')
            || $request->session()->has('customer_portal.verified_until');

        $request->session()->forget([
            'customer_portal.verified_phone',
            'customer_portal.verified_until',
        ]);

        return to_route('customer-portal.access.create')
            ->with('customer_portal.session_expired', $sessionExpired);
    }
}
