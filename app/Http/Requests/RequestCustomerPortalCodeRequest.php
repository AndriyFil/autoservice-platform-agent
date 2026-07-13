<?php

namespace App\Http\Requests;

use App\Domain\Shared\ValueObjects\Phone;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class RequestCustomerPortalCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, Closure|string>> */
    public function rules(): array
    {
        return [
            'phone' => [
                'required',
                'string',
                'max:50',
                static function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_string($value) && strlen((new Phone($value))->normalize()) < 7) {
                        $fail('Please enter a valid phone number.');
                    }
                },
            ],
        ];
    }

    public function phone(): string
    {
        return (string) $this->validated('phone');
    }
}
