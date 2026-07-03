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
        return __("estimates.statuses.{$this->value}");
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::Draft => in_array($status, [self::Generated, self::Cancelled], true),
            self::Generated => in_array($status, [self::Approved, self::Rejected, self::Superseded, self::Cancelled], true),
            self::Approved, self::Rejected => $status === self::Superseded,
            self::Superseded, self::Cancelled => false,
        };
    }
}
