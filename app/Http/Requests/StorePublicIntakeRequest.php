<?php

namespace App\Http\Requests;

use App\Domain\Shared\ValueObjects\Phone;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StorePublicIntakeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $vehicle = $this->input('vehicle');

        if (! is_array($vehicle)) {
            return;
        }

        $this->merge([
            'vehicle' => [
                'brand' => $this->nullableTrimmedString($vehicle['brand'] ?? null),
                'model' => $this->nullableTrimmedString($vehicle['model'] ?? null),
                'year' => $vehicle['year'] ?? null,
                'license_plate' => $this->nullableTrimmedString($vehicle['license_plate'] ?? null),
            ],
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, Closure|string>>
     */
    public function rules(): array
    {
        return [
            'message' => [
                'required',
                'string',
                'max:5000',
                static function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_string($value) && trim($value) === '') {
                        $fail('Please describe what is happening with your car.');
                    }
                },
            ],
            'phone' => [
                'required',
                'string',
                'max:50',
                static function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }

                    $normalizedPhone = (new Phone($value))->normalize();

                    if (strlen($normalizedPhone) < 7) {
                        $fail('Please provide a phone number so a service advisor can contact you.');
                    }
                },
            ],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'workshop_id' => ['required', 'integer', 'exists:workshops,id'],
            'vehicle' => ['array'],
            'vehicle.brand' => ['nullable', 'string', 'max:255'],
            'vehicle.model' => ['nullable', 'string', 'max:255'],
            'vehicle.year' => ['nullable', 'integer', 'between:1886,2100'],
            'vehicle.license_plate' => ['nullable', 'string', 'max:255'],
            // Honeypot: hidden field real customers never fill; bots do.
            'website' => ['prohibited'],
        ];
    }

    public function message(): string
    {
        return $this->validated('message');
    }

    public function phone(): string
    {
        return trim((string) $this->validated('phone'));
    }

    public function workshopId(): int
    {
        return (int) $this->validated('workshop_id');
    }

    public function customerName(): ?string
    {
        $name = trim((string) $this->validated('customer_name', ''));

        return $name === '' ? null : $name;
    }

    /**
     * @return array{brand: ?string, model: ?string, year: ?int, license_plate: ?string}
     */
    public function vehicle(): array
    {
        $vehicle = $this->validated('vehicle', []);

        return [
            'brand' => $vehicle['brand'] ?? null,
            'model' => $vehicle['model'] ?? null,
            'year' => isset($vehicle['year']) ? (int) $vehicle['year'] : null,
            'license_plate' => $vehicle['license_plate'] ?? null,
        ];
    }

    private function nullableTrimmedString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $value;
    }
}
