<?php

namespace App\Domain\Workshops\Exceptions;

use Illuminate\Validation\ValidationException;

class LastOwnerCannotBeDemoted extends ValidationException
{
    public static function forRoleField(): static
    {
        return static::withMessages([
            'role' => 'A workshop must keep at least one owner.',
        ]);
    }
}
