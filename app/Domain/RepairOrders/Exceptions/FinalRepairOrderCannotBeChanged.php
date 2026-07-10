<?php

namespace App\Domain\RepairOrders\Exceptions;

use DomainException;

class FinalRepairOrderCannotBeChanged extends DomainException
{
    public static function lines(): self
    {
        return new self('Repair order lines cannot be changed after the repair order is closed.');
    }

    public static function approvalRequirement(): self
    {
        return new self('Estimate approval requirement cannot be changed after the repair order is closed.');
    }
}
