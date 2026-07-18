<?php

namespace App\Http\Controllers;

use App\Domain\BookingRequests\Actions\SubmitPublicIntakeAction;
use App\Domain\CustomerPortal\Queries\CustomerRequestIndexQuery;
use App\Domain\Workshops\Queries\AvailablePublicWorkshopsQuery;
use App\Http\Requests\StorePublicIntakeRequest;
use App\Support\Urls\AppUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class PublicIntakeController extends Controller
{
    public function create(
        Request $request,
        AvailablePublicWorkshopsQuery $availableWorkshops,
        CustomerRequestIndexQuery $customerRequests,
    ): Response {
        $verifiedPhone = $this->verifiedPhone($request);
        $history = $verifiedPhone !== null ? $customerRequests->handle($verifiedPhone) : null;
        $props = [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'adminLoginUrl' => AppUrl::adminPath('/login'),
            'adminRegisterUrl' => AppUrl::adminPath('/register'),
            'workshops' => $availableWorkshops->options(),
            'intakeSubmitted' => session()->get('intake_submitted', false),
            'intakeWorkshopName' => session()->get('intake_workshop_name'),
        ];

        if ($history !== null) {
            $props['recentRequests'] = $history['recent'];
            $props['hasMoreRequests'] = $history['hasMore'];
        }

        return Inertia::render('Welcome', $props);
    }

    public function store(
        StorePublicIntakeRequest $request,
        AvailablePublicWorkshopsQuery $availableWorkshops,
        SubmitPublicIntakeAction $submitPublicIntake,
    ): RedirectResponse {
        $workshop = $availableWorkshops->resolve($request->workshopId());

        $submitPublicIntake->handle($workshop, $request->message(), $request->phone(), $request->customerName(), $request->vehicle());

        return to_route('home')->with([
            'intake_submitted' => true,
            'intake_workshop_name' => $workshop->name,
        ]);
    }

    private function verifiedPhone(Request $request): ?string
    {
        $phone = $request->session()->get('customer_portal.verified_phone');
        $verifiedUntil = $request->session()->get('customer_portal.verified_until');

        if (! is_string($phone) || $phone === '' || ! is_int($verifiedUntil) || now()->timestamp >= $verifiedUntil) {
            return null;
        }

        return $phone;
    }
}
