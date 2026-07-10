<?php

namespace App\Domain\RepairOrders\Enums;

enum RepairOrderStatus: string
{
    case Draft = 'draft';
    case Estimated = 'estimated';
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
            self::Draft => in_array($status, [self::Estimated, self::InProgress, self::Cancelled], true),
            self::Estimated => in_array($status, [self::InProgress, self::Cancelled], true),
            self::InProgress => in_array($status, [self::Completed, self::Cancelled], true),
            self::Completed, self::Cancelled => false,
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled], true);
    }
}
