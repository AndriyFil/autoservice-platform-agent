<?php

namespace App\Http\Requests;

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
            // Honeypot: hidden field real customers never fill; bots do.
            'website' => ['prohibited'],
        ];
    }

    public function message(): string
    {
        return $this->validated('message');
    }
}
