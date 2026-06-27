<?php

namespace App\Enums;

enum BookingRequestStatus: string
{
    case Submitted = 'submitted';
    case New = 'new';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::New => 'New',
            self::Confirmed => 'Confirmed',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::Submitted => in_array($status, [self::Confirmed, self::Rejected, self::Cancelled], true),
            self::New => in_array($status, [self::Confirmed, self::Rejected, self::Cancelled], true),
            self::Confirmed => $status === self::Cancelled,
            self::Rejected, self::Cancelled => false,
        };
    }
}
