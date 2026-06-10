<?php

namespace App\Enums;

enum BookingRequestStatus: string
{
    case New = 'new';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
