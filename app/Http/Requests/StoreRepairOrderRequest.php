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
            'customer_id' => ['required', 'integer'],
            'vehicle_id' => ['nullable', 'integer'],
            'booking_request_id' => ['nullable', 'integer'],
            'problem_description' => ['required', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
