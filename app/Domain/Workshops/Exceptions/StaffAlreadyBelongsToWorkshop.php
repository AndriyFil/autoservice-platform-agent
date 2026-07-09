<?php

namespace App\Domain\Workshops\Exceptions;

use Illuminate\Validation\ValidationException;

class StaffAlreadyBelongsToWorkshop extends ValidationException
{
    public static function forEmailField(): static
    {
        return static::withMessages([
            'email' => 'This user is already a member of this workshop.',
        ]);
    }
}
