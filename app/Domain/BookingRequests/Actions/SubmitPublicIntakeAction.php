<?php

namespace App\Domain\BookingRequests\Actions;

use App\Domain\BookingRequests\Enums\BookingRequestStatus;
use App\Domain\Shared\ValueObjects\Phone;
use App\Models\BookingRequest;
use App\Models\Workshop;
use App\Support\Intake\IntakeExtractorInterface;
use Throwable;

class SubmitPublicIntakeAction
{
    public function __construct(
        private readonly IntakeExtractorInterface $intakeExtractor,
    ) {}

    public function handle(Workshop $workshop, string $message, string $phone): BookingRequest
    {
        $bookingRequest = BookingRequest::create([
            'workshop_id' => $workshop->id,
            'customer_id' => null,
            'vehicle_id' => null,
            'created_by_user_id' => null,
            'customer_name' => null,
            'customer_phone' => $phone,
            'customer_phone_normalized' => (new Phone($phone))->normalize(),
            'problem_description' => $message,
            'original_message' => $message,
            'preferred_date' => null,
            'status' => BookingRequestStatus::New,
        ]);

        try {
            $extractionResult = $this->intakeExtractor->extract($message);
        } catch (Throwable) {
            return $bookingRequest;
        }

        $problemSummary = is_string($extractionResult->problemSummary)
            ? trim($extractionResult->problemSummary)
            : '';

        if ($problemSummary !== '') {
            $bookingRequest->update([
                'problem_description' => $problemSummary,
            ]);
        }

        return $bookingRequest->refresh();
    }
}
