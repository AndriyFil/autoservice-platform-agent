<?php

namespace App\Http\Controllers;

use App\Domain\Customers\Actions\CreateCustomerVehicleAction;
use App\Domain\Customers\Actions\UpdateCustomerAction;
use App\Domain\Customers\Actions\UpdateCustomerVehicleAction;
use App\Domain\Customers\Queries\CustomerIndexQuery;
use App\Domain\Customers\Queries\CustomerShowQuery;
use App\Http\Requests\StoreCustomerVehicleRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Requests\UpdateCustomerVehicleRequest;
use App\Models\Customer;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(
        Request $request,
        CustomerIndexQuery $customerIndexQuery,
    ): Response {
        $activeWorkshopUser = $request->attributes->get('activeWorkshopUser');
        $activeWorkshop = $activeWorkshopUser->workshop;

        return Inertia::render('Customers/Index', [
            'activeWorkshop' => [
                'id' => $activeWorkshop->id,
                'name' => $activeWorkshop->name,
                'slug' => $activeWorkshop->slug,
            ],
            'customers' => $customerIndexQuery->handle($activeWorkshopUser, $request->string('search')->toString()),
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    public function show(
        Request $request,
        Customer $customer,
        CustomerShowQuery $customerShowQuery,
    ): Response {
        $activeWorkshopUser = $request->attributes->get('activeWorkshopUser');
        $activeWorkshop = $activeWorkshopUser->workshop;

        return Inertia::render('Customers/Show', [
            'activeWorkshop' => [
                'id' => $activeWorkshop->id,
                'name' => $activeWorkshop->name,
                'slug' => $activeWorkshop->slug,
            ],
            'customer' => $customerShowQuery->handle($activeWorkshopUser, $customer),
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
