<?php

namespace App\Http\Controllers;

use App\Queries\Dashboard\DashboardBookingRequestsQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function show(
        Request $request,
        DashboardBookingRequestsQuery $bookingRequestsQuery,
    ): Response {
        $activeWorkshopUser = $request->attributes->get('activeWorkshopUser');
        $activeWorkshop = $activeWorkshopUser->workshop;

        return Inertia::render('Dashboard', [
            'activeWorkshop' => [
                'id' => $activeWorkshop->id,
                'name' => $activeWorkshop->name,
                'slug' => $activeWorkshop->slug,
            ],
            'bookingRequests' => $bookingRequestsQuery->handle($activeWorkshopUser),
        ]);
    }
}
