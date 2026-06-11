<?php

namespace App\Http\Controllers;

use App\Queries\Dashboard\DashboardBookingRequestsQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function show(Request $request, DashboardBookingRequestsQuery $bookingRequestsQuery): Response|RedirectResponse
    {
        $workshopUsers = $request->user()
            ->workshopUsers()
            ->with('workshop')
            ->orderBy('id')
            ->get();

        if ($workshopUsers->isEmpty()) {
            return to_route('workshop-onboarding.create');
        }

        $activeWorkshopId = $request->session()->get('active_workshop_id');
        $activeWorkshopUser = $workshopUsers->firstWhere('workshop_id', $activeWorkshopId);

        if (! $activeWorkshopUser) {
            $activeWorkshopUser = $workshopUsers->first();
            $request->session()->put('active_workshop_id', $activeWorkshopUser->workshop_id);
        }

        $activeWorkshop = $activeWorkshopUser->workshop;

        return Inertia::render('Dashboard', [
            'activeWorkshop' => [
                'id' => $activeWorkshop->id,
                'name' => $activeWorkshop->name,
                'slug' => $activeWorkshop->slug,
            ],
            'bookingRequests' => $bookingRequestsQuery->handle($activeWorkshop),
        ]);
    }
}
