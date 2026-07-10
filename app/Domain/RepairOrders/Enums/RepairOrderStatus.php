<?php

namespace App\Domain\RepairOrders\Enums;

enum RepairOrderStatus: string
{
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __("repair_orders.repair_order_statuses.{$this->value}");
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->manualTransitions(), true);
    }

    /**
     * @return array<int, self>
     */
    public function manualTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::InProgress, self::Cancelled],
            self::InProgress => [self::Draft, self::Completed, self::Cancelled],
            self::Completed, self::Cancelled => [],
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled], true);
    }
}
