<?php

namespace Tests\Unit;

use App\Enums\EstimateStatus;
use PHPUnit\Framework\TestCase;

class EstimateStatusTest extends TestCase
{
    public function test_draft_can_transition_to_generated_and_cancelled_only(): void
    {
        $this->assertTrue(EstimateStatus::Draft->canTransitionTo(EstimateStatus::Generated));
        $this->assertTrue(EstimateStatus::Draft->canTransitionTo(EstimateStatus::Cancelled));
        $this->assertFalse(EstimateStatus::Draft->canTransitionTo(EstimateStatus::Approved));
        $this->assertFalse(EstimateStatus::Draft->canTransitionTo(EstimateStatus::Rejected));
        $this->assertFalse(EstimateStatus::Draft->canTransitionTo(EstimateStatus::Superseded));
    }

    public function test_generated_can_transition_to_review_outcomes(): void
    {
        $this->assertTrue(EstimateStatus::Generated->canTransitionTo(EstimateStatus::Approved));
        $this->assertTrue(EstimateStatus::Generated->canTransitionTo(EstimateStatus::Rejected));
        $this->assertTrue(EstimateStatus::Generated->canTransitionTo(EstimateStatus::Superseded));
        $this->assertTrue(EstimateStatus::Generated->canTransitionTo(EstimateStatus::Cancelled));
        $this->assertFalse(EstimateStatus::Generated->canTransitionTo(EstimateStatus::Draft));
    }

    public function test_reviewed_estimates_can_only_be_superseded(): void
    {
        foreach ([EstimateStatus::Approved, EstimateStatus::Rejected] as $status) {
            $this->assertTrue($status->canTransitionTo(EstimateStatus::Superseded));
            $this->assertFalse($status->canTransitionTo(EstimateStatus::Cancelled));
            $this->assertFalse($status->canTransitionTo(EstimateStatus::Generated));
        }
    }

    public function test_terminal_statuses_allow_no_transitions(): void
    {
        foreach ([EstimateStatus::Superseded, EstimateStatus::Cancelled] as $terminal) {
            foreach (EstimateStatus::cases() as $target) {
                $this->assertFalse($terminal->canTransitionTo($target));
            }
        }
    }
}
