<?php

namespace App\Http\Requests;

class StoreCustomerVehicleRequest extends DashboardFormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'make' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.now()->addYear()->year],
            'plate' => ['nullable', 'string', 'max:255'],
        ];
    }
}
