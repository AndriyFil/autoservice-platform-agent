<?php

namespace Tests\Unit;

use App\Enums\RepairOrderStatus;
use PHPUnit\Framework\TestCase;

class RepairOrderStatusTest extends TestCase
{
    public function test_draft_can_move_to_estimated_in_progress_or_cancelled(): void
    {
        $this->assertTrue(RepairOrderStatus::Draft->canTransitionTo(RepairOrderStatus::Estimated));
        $this->assertTrue(RepairOrderStatus::Draft->canTransitionTo(RepairOrderStatus::InProgress));
        $this->assertTrue(RepairOrderStatus::Draft->canTransitionTo(RepairOrderStatus::Cancelled));
        $this->assertFalse(RepairOrderStatus::Draft->canTransitionTo(RepairOrderStatus::Completed));
    }

    public function test_estimated_can_move_to_in_progress_or_cancelled(): void
    {
        $this->assertTrue(RepairOrderStatus::Estimated->canTransitionTo(RepairOrderStatus::InProgress));
        $this->assertTrue(RepairOrderStatus::Estimated->canTransitionTo(RepairOrderStatus::Cancelled));
        $this->assertFalse(RepairOrderStatus::Estimated->canTransitionTo(RepairOrderStatus::Completed));
        $this->assertFalse(RepairOrderStatus::Estimated->canTransitionTo(RepairOrderStatus::Draft));
    }

    public function test_in_progress_can_move_to_completed_or_cancelled(): void
    {
        $this->assertTrue(RepairOrderStatus::InProgress->canTransitionTo(RepairOrderStatus::Completed));
        $this->assertTrue(RepairOrderStatus::InProgress->canTransitionTo(RepairOrderStatus::Cancelled));
        $this->assertFalse(RepairOrderStatus::InProgress->canTransitionTo(RepairOrderStatus::Estimated));
    }

    public function test_terminal_statuses_cannot_transition(): void
    {
        foreach ([RepairOrderStatus::Completed, RepairOrderStatus::Cancelled] as $status) {
            foreach (RepairOrderStatus::cases() as $targetStatus) {
                $this->assertFalse($status->canTransitionTo($targetStatus));
            }
        }
    }

    public function test_approved_is_not_a_repair_order_status(): void
    {
        $this->assertNotContains('approved', array_map(
            fn (RepairOrderStatus $status): string => $status->value,
            RepairOrderStatus::cases(),
        ));
    }
}
