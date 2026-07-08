<?php

namespace App\Http\Requests;

class StoreRepairOrderRequest extends DashboardFormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', 'required_without:booking_request_id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'vehicle_id' => ['nullable', 'integer'],
            'booking_request_id' => ['nullable', 'integer'],
            'problem_description' => ['required', 'string', 'max:5000'],
            'requires_estimate_approval' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'new_vehicle' => ['nullable', 'array'],
            'new_vehicle.make' => ['nullable', 'string', 'max:255'],
            'new_vehicle.model' => ['nullable', 'string', 'max:255'],
            'new_vehicle.year' => ['nullable', 'integer', 'min:1886', 'max:2100'],
            'new_vehicle.plate' => ['nullable', 'string', 'max:255'],
        ];
    }
}
