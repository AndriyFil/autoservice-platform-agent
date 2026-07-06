<?php

namespace App\Enums;

enum MissingIntakeField: string
{
    case Phone = 'phone';

    public function label(): string
    {
        return match ($this) {
            self::Phone => 'Missing phone',
        };
    }
}
