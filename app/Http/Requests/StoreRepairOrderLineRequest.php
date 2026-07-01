<?php

namespace App\Http\Requests;

use App\Enums\RepairOrderLineType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRepairOrderLineRequest extends FormRequest
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
            'type' => ['required', Rule::enum(RepairOrderLineType::class)],
            'description' => ['required', 'string', 'max:1000'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'unit_price_cents' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
