<?php

namespace App\Domain\Workshops\Enums;

enum WorkshopUserRole: string
{
    case Owner = 'owner';
    case Staff = 'staff';
}
