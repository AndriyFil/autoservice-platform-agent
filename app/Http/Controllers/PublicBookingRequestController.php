<?php

namespace App\Http\Controllers;

use App\Actions\BookingRequests\CreatePublicBookingRequestAction;
use App\Http\Requests\StorePublicBookingRequestRequest;
use App\Models\Workshop;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PublicBookingRequestController extends Controller
{
    public function create(Workshop $workshop): Response
    {
        return Inertia::render('PublicBookingRequests/Create', [
            'workshop' => [
                'name' => $workshop->name,
                'slug' => $workshop->slug,
            ],
        ]);
    }

    public function store(
        StorePublicBookingRequestRequest $request,
        Workshop $workshop,
        CreatePublicBookingRequestAction $createPublicBookingRequest,
    ): RedirectResponse {
        $createPublicBookingRequest->handle($workshop, $request->validated());

        return to_route('public-booking-requests.success', $workshop->slug);
    }

    public function success(Workshop $workshop): Response
    {
        return Inertia::render('PublicBookingRequests/Success', [
            'workshop' => [
                'name' => $workshop->name,
                'slug' => $workshop->slug,
            ],
        ]);
    }
}
