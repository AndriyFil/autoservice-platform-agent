<?php

namespace App\Http\Controllers;

use App\Actions\BookingRequests\SubmitIntakeRequestAction;
use App\Http\Requests\StorePublicIntakeRequest;
use Illuminate\Http\RedirectResponse;

class PublicIntakeController extends Controller
{
    public function store(
        StorePublicIntakeRequest $request,
        SubmitIntakeRequestAction $submitIntakeRequest,
    ): RedirectResponse {
        $submitIntakeRequest->handle($request->message());

        return to_route('home')->with('intake_submitted', true);
    }
}
