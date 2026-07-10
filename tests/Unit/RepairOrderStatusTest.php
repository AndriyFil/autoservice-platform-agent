<?php

namespace Tests\Unit;

use App\Domain\RepairOrders\Enums\RepairOrderStatus;
use PHPUnit\Framework\TestCase;

class RepairOrderStatusTest extends TestCase
{
    public function test_draft_can_move_to_in_progress_or_cancelled(): void
    {
        $this->assertTrue(RepairOrderStatus::Draft->canTransitionTo(RepairOrderStatus::InProgress));
        $this->assertTrue(RepairOrderStatus::Draft->canTransitionTo(RepairOrderStatus::Cancelled));
        $this->assertFalse(RepairOrderStatus::Draft->canTransitionTo(RepairOrderStatus::Completed));
    }

    public function test_in_progress_can_move_to_draft_completed_or_cancelled(): void
    {
        $this->assertTrue(RepairOrderStatus::InProgress->canTransitionTo(RepairOrderStatus::Draft));
        $this->assertTrue(RepairOrderStatus::InProgress->canTransitionTo(RepairOrderStatus::Completed));
        $this->assertTrue(RepairOrderStatus::InProgress->canTransitionTo(RepairOrderStatus::Cancelled));
    }

    public function test_terminal_statuses_cannot_transition(): void
    {
        foreach ([RepairOrderStatus::Completed, RepairOrderStatus::Cancelled] as $status) {
            foreach (RepairOrderStatus::cases() as $targetStatus) {
                $this->assertFalse($status->canTransitionTo($targetStatus));
            }
        }
    }

    public function test_completed_and_cancelled_are_final_statuses(): void
    {
        $this->assertFalse(RepairOrderStatus::Draft->isFinal());
        $this->assertFalse(RepairOrderStatus::InProgress->isFinal());
        $this->assertTrue(RepairOrderStatus::Completed->isFinal());
        $this->assertTrue(RepairOrderStatus::Cancelled->isFinal());
    }

    public function test_approved_is_not_a_repair_order_status(): void
    {
        $this->assertNotContains('approved', array_map(
            fn (RepairOrderStatus $status): string => $status->value,
            RepairOrderStatus::cases(),
        ));
    }

    public function test_estimated_is_not_a_repair_order_status(): void
    {
        $this->assertNotContains('estimated', array_map(
            fn (RepairOrderStatus $status): string => $status->value,
            RepairOrderStatus::cases(),
        ));
    }
}
