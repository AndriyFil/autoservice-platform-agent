<?php

namespace App\Http\Controllers;

use App\Actions\Customers\CreateCustomerVehicleAction;
use App\Actions\Customers\UpdateCustomerAction;
use App\Actions\Customers\UpdateCustomerVehicleAction;
use App\Http\Requests\StoreCustomerVehicleRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Requests\UpdateCustomerVehicleRequest;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Queries\Customers\CustomerDetailsQuery;
use App\Queries\Customers\CustomerListQuery;
use Illuminate\Http\RedirectResponse;
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
            'customers' => $customerListQuery->handle($activeWorkshopUser, $request->string('search')->toString()),
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
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

    public function update(
        UpdateCustomerRequest $request,
        Customer $customer,
        UpdateCustomerAction $updateCustomerAction,
    ): RedirectResponse {
        $updateCustomerAction->handle(
            $request->attributes->get('activeWorkshopUser'),
            $customer,
            $request->validated(),
        );

        return to_route('customers.show', $customer)
            ->with('status', 'Customer updated.');
    }

    public function storeVehicle(
        StoreCustomerVehicleRequest $request,
        Customer $customer,
        CreateCustomerVehicleAction $createCustomerVehicleAction,
    ): RedirectResponse {
        $createCustomerVehicleAction->handle(
            $request->attributes->get('activeWorkshopUser'),
            $customer,
            $request->validated(),
        );

        return to_route('customers.show', $customer)
            ->with('status', 'Vehicle added.');
    }

    public function updateVehicle(
        UpdateCustomerVehicleRequest $request,
        Customer $customer,
        Vehicle $vehicle,
        UpdateCustomerVehicleAction $updateCustomerVehicleAction,
    ): RedirectResponse {
        $updateCustomerVehicleAction->handle(
            $request->attributes->get('activeWorkshopUser'),
            $customer,
            $vehicle,
            $request->validated(),
        );

        return to_route('customers.show', $customer)
            ->with('status', 'Vehicle updated.');
    }
}
