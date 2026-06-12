<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Queries\Customers\CustomerDetailsQuery;
use App\Queries\Customers\CustomerListQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(
        Request $request,
        CustomerListQuery $customerListQuery,
    ): Response {
        $activeWorkshopUser = $request->attributes->get('activeWorkshopUser');
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
        CustomerDetailsQuery $customerDetailsQuery,
    ): Response {
        $activeWorkshopUser = $request->attributes->get('activeWorkshopUser');
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
