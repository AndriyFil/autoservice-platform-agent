<?php

namespace App\Http\Controllers;

use App\Actions\Workshops\CreateInitialWorkshopAction;
use App\Http\Requests\StoreWorkshopOnboardingRequest;
use App\Models\WorkshopUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkshopOnboardingController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if (WorkshopUser::query()->where('user_id', $request->user()->id)->exists()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('WorkshopOnboarding');
    }

    public function store(
        StoreWorkshopOnboardingRequest $request,
        CreateInitialWorkshopAction $createInitialWorkshop,
    ): RedirectResponse {
        $workshop = $createInitialWorkshop->handle($request->user(), $request->validated());

        $request->session()->put('active_workshop_id', $workshop->id);

        return redirect()->route('dashboard');
    }
}
