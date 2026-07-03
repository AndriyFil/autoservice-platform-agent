<?php

namespace App\Enums;

enum RepairOrderStatus: string
{
    case Draft = 'draft';
    case Estimated = 'estimated';
    case Approved = 'approved';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __("repair_orders.repair_order_statuses.{$this->value}");
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::Draft => in_array($status, [self::Estimated, self::Approved, self::InProgress, self::Completed, self::Cancelled], true),
            self::Estimated => in_array($status, [self::Approved, self::Cancelled], true),
            self::Approved => in_array($status, [self::InProgress, self::Cancelled], true),
            self::InProgress => in_array($status, [self::Completed, self::Cancelled], true),
            self::Completed, self::Cancelled => false,
        };
    }
}
