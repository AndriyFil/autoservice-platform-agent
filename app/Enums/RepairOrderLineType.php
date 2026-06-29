<?php

namespace App\Enums;

enum RepairOrderLineType: string
{
    case Labor = 'labor';
    case Part = 'part';
    case Fee = 'fee';
    case Discount = 'discount';

    public function label(): string
    {
        return match ($this) {
            self::Labor => 'Labor',
            self::Part => 'Part',
            self::Fee => 'Fee',
            self::Discount => 'Discount',
        };
    }
}
