<?php

namespace App\Http\Controllers;

use App\Queries\Dashboard\DashboardBookingRequestsQuery;
use App\Support\ActiveWorkshopMembershipResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function show(
        Request $request,
        ActiveWorkshopMembershipResolver $activeWorkshopMembershipResolver,
        DashboardBookingRequestsQuery $bookingRequestsQuery,
    ): Response|RedirectResponse {
        $activeWorkshopUser = $activeWorkshopMembershipResolver->resolve($request->user(), $request->session());

        if (! $activeWorkshopUser) {
            return to_route('workshop-onboarding.create');
        }

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
