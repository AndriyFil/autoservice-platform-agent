<?php

namespace App\Enums;

enum MissingIntakeField: string
{
    case Phone = 'phone';
    case Vehicle = 'vehicle';
    case PreferredTime = 'preferred_time';

    public function label(): string
    {
        return match ($this) {
            self::Phone => 'Missing phone',
            self::Vehicle => 'Missing vehicle',
            self::PreferredTime => 'Missing preferred time',
        };
    }
}
