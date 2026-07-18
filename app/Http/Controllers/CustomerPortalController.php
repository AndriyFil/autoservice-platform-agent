<?php

namespace App\Http\Controllers;

use App\Domain\CustomerPortal\Queries\CustomerRequestIndexQuery;
use App\Domain\CustomerPortal\Queries\CustomerRequestShowQuery;
use App\Support\Urls\AppUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class CustomerPortalController extends Controller
{
    public function index(Request $request, CustomerRequestIndexQuery $query): Response
    {
        $verifiedPhone = (string) $request->session()->get('customer_portal.verified_phone');
        $history = $query->handle($verifiedPhone);

        return Inertia::render('CustomerPortal/Index', [
            'recentRequests' => $history['recent'],
            'hasMoreRequests' => $history['hasMore'],
            'requests' => $history['requests'],
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'adminLoginUrl' => AppUrl::adminPath('/login'),
            'adminRegisterUrl' => AppUrl::adminPath('/register'),
        ]);
    }

    public function show(
        Request $request,
        int $bookingRequest,
        CustomerRequestIndexQuery $indexQuery,
        CustomerRequestShowQuery $showQuery,
    ): Response {
        $verifiedPhone = (string) $request->session()->get('customer_portal.verified_phone');
        $history = $indexQuery->handle($verifiedPhone);

        return Inertia::render('CustomerPortal/Show', [
            'request' => $showQuery->handle($verifiedPhone, $bookingRequest),
            'recentRequests' => $history['recent'],
            'hasMoreRequests' => $history['hasMore'],
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'adminLoginUrl' => AppUrl::adminPath('/login'),
            'adminRegisterUrl' => AppUrl::adminPath('/register'),
        ]);
    }
}
