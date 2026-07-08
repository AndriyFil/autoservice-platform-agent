<?php

namespace App\Http\Requests;

class UpdateCustomerRequest extends DashboardFormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
        ];
    }
}
