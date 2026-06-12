<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRepairOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
        ];
    }
}
