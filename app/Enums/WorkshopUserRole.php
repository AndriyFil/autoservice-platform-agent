<?php

namespace App\Enums;

enum WorkshopUserRole: string
{
    case Owner = 'owner';
    case Staff = 'staff';
}
