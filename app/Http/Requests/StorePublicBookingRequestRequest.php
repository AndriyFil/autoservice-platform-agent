<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePublicBookingRequestRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $vehicle = is_array($this->input('vehicle')) ? $this->input('vehicle') : [];

        $this->merge([
            'customer_name' => $this->trimmedString($this->input('customer_name')),
            'customer_phone' => $this->trimmedString($this->input('customer_phone')),
            'problem_description' => $this->trimmedString($this->input('problem_description')),
            'preferred_date' => $this->nullableTrimmedString($this->input('preferred_date')),
            'vehicle' => [
                'brand' => $this->nullableTrimmedString($vehicle['brand'] ?? null),
                'model' => $this->nullableTrimmedString($vehicle['model'] ?? null),
                'license_plate' => $this->nullableTrimmedString($vehicle['license_plate'] ?? null),
            ],
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:255', 'regex:/[0-9]/'],
            'problem_description' => ['required', 'string'],
            'preferred_date' => ['nullable', 'date'],
            'vehicle.brand' => ['nullable', 'string', 'max:255'],
            'vehicle.model' => ['nullable', 'string', 'max:255'],
            'vehicle.license_plate' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function trimmedString(mixed $value): string

    {

        return is_string($value) ? trim($value) : '';

    }

    private function nullableTrimmedString(mixed $value): ?string

    {

        $value = $this->trimmedString($value);

        return $value === '' ? null : $value;

    }
}
