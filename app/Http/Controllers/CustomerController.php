<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Queries\Customers\CustomerDetailsQuery;
use App\Queries\Customers\CustomerListQuery;
use App\Support\ActiveWorkshopMembershipResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(
        Request $request,
        ActiveWorkshopMembershipResolver $activeWorkshopMembershipResolver,
        CustomerListQuery $customerListQuery,
    ): Response|RedirectResponse {
        $activeWorkshopUser = $activeWorkshopMembershipResolver->resolve($request->user(), $request->session());

        if (! $activeWorkshopUser) {
            return to_route('workshop-onboarding.create');
        }

        $activeWorkshop = $activeWorkshopUser->workshop;

        return Inertia::render('Customers/Index', [
            'activeWorkshop' => [
                'id' => $activeWorkshop->id,
                'name' => $activeWorkshop->name,
                'slug' => $activeWorkshop->slug,
            ],
            'customers' => $customerListQuery->handle($activeWorkshopUser),
        ]);
    }

    public function show(
        Request $request,
        Customer $customer,
        ActiveWorkshopMembershipResolver $activeWorkshopMembershipResolver,
        CustomerDetailsQuery $customerDetailsQuery,
    ): Response|RedirectResponse {
        $activeWorkshopUser = $activeWorkshopMembershipResolver->resolve($request->user(), $request->session());

        if (! $activeWorkshopUser) {
            return to_route('workshop-onboarding.create');
        }

        $activeWorkshop = $activeWorkshopUser->workshop;

        return Inertia::render('Customers/Show', [
            'activeWorkshop' => [
                'id' => $activeWorkshop->id,
                'name' => $activeWorkshop->name,
                'slug' => $activeWorkshop->slug,
            ],
            'customer' => $customerDetailsQuery->handle($activeWorkshopUser, $customer),
        ]);
    }
}
