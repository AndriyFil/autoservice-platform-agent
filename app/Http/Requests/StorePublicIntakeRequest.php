<?php

namespace App\Http\Requests;

use App\Support\PhoneNormalizer;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StorePublicIntakeRequest extends FormRequest
{
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

                    $normalizedPhone = app(PhoneNormalizer::class)->normalize($value);

                    if (strlen($normalizedPhone) < 7) {
                        $fail('Please provide a phone number so a service advisor can contact you.');
                    }
                },
            ],
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
        return app(PhoneNormalizer::class)->normalize($this->validated('phone'));
    }
}
