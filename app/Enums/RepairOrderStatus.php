<?php

namespace App\Enums;

enum RepairOrderStatus: string
{
    case Open = 'open';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::Open => in_array($status, [self::Completed, self::Cancelled], true),
            self::Completed, self::Cancelled => false,
        };
    }
}
