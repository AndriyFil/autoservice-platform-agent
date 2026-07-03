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
        return __("repair_orders.line_types.{$this->value}");
    }
}
