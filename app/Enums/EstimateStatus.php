<?php

namespace App\Enums;

enum EstimateStatus: string
{
    case Draft = 'draft';
    case Generated = 'generated';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Superseded = 'superseded';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __("repair_orders.estimate_statuses.{$this->value}");
    }
}
