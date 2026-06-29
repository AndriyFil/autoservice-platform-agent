<?php

namespace App\Actions\BookingRequests;

use App\Enums\BookingRequestStatus;
use App\Models\BookingRequest;
use App\Support\Intake\IntakeExtractorInterface;

class SubmitIntakeRequestAction
{
    public function __construct(
        private readonly IntakeExtractorInterface $intakeExtractor,
    ) {}

    public function handle(string $message): BookingRequest
    {
        $extractionResult = $this->intakeExtractor->extract($message);

        return BookingRequest::create([
            'workshop_id' => null,
            'customer_id' => null,
            'vehicle_id' => null,
            'created_by_user_id' => null,
            'customer_name' => null,
            'customer_phone' => $extractionResult->phone,
            'problem_description' => $message,
            'original_message' => $message,
            'preferred_date' => null,
            'status' => BookingRequestStatus::Submitted,
        ]);
    }
}
