<?php

namespace App\Http\Controllers;

use App\Actions\BookingRequests\SubmitIntakeRequestAction;
use App\Http\Requests\StorePublicIntakeRequest;
use App\Models\Workshop;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PublicIntakeController extends Controller
{
    public function create(Workshop $workshop): Response
    {
        return Inertia::render('PublicIntake', [
            'workshop' => [
                'name' => $workshop->name,
                'slug' => $workshop->slug,
            ],
            'intakeSubmitted' => session()->get('intake_submitted', false),
        ]);
    }

    public function store(
        StorePublicIntakeRequest $request,
        Workshop $workshop,
        SubmitIntakeRequestAction $submitIntakeRequest,
    ): RedirectResponse {
        $submitIntakeRequest->handle($workshop, $request->message());

        return to_route('public-intake.create', $workshop)->with('intake_submitted', true);
    }
}
