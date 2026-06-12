<?php

namespace App\Support;

class PhoneNormalizer
{
    public function normalize(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }
}
