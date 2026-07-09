<?php

namespace App\Domain\Workshops\Exceptions;

use Illuminate\Validation\ValidationException;

class LastOwnerCannotBeRemoved extends ValidationException
{
    public static function forMembershipField(): static
    {
        return static::withMessages([
            'membership' => 'A workshop must keep at least one owner.',
        ]);
    }
}
