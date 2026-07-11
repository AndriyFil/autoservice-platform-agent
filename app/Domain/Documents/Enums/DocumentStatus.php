<?php

namespace App\Domain\Documents\Enums;

enum DocumentStatus: string
{
    case Generated = 'generated';
    case Failed = 'failed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Generated => 'Generated',
            self::Failed => 'Failed',
            self::Archived => 'Archived',
        };
    }
}
