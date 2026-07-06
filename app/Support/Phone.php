<?php

namespace App\Support;

class Phone
{
    public function __construct(
        private readonly string $value,
    ) {}

    public function normalize(): string
    {
        $phone = trim($this->value);
        $hasLeadingPlus = str_starts_with($phone, '+');
        $cleaned = preg_replace('/[\s\-()]+/', '', $phone) ?? '';
        $digits = preg_replace('/\D+/', '', $cleaned) ?? '';

        if (preg_match('/^0\d{9}$/', $digits) === 1) {
            return '+38'.$digits;
        }

        if (preg_match('/^380\d{9}$/', $digits) === 1) {
            return '+'.$digits;
        }

        return $hasLeadingPlus ? '+'.$digits : $digits;
    }

    public function normalizeLegacyDigits(): string
    {
        return preg_replace('/\D+/', '', $this->value) ?? '';
    }
}
