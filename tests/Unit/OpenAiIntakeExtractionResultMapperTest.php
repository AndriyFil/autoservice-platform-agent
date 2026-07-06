<?php

namespace Tests\Unit;

use App\Enums\MissingIntakeField;
use App\Support\Intake\OpenAiIntakeExtractionResultMapper;
use PHPUnit\Framework\TestCase;

class OpenAiIntakeExtractionResultMapperTest extends TestCase
{
    public function test_maps_full_valid_payload_to_intake_extraction_result(): void
    {
        $result = $this->mapper()->map([
            'phone' => '380501112233',
            'vehicle' => [
                'make' => 'Opel',
                'model' => 'Insignia',
                'plate' => 'AA1234BB',
            ],
            'preferred_time_text' => 'tomorrow morning',
            'problem_summary' => 'Check engine light came on.',
            'missing_next_field' => null,
            'confidence' => 0.85,
        ]);

        $this->assertSame('380501112233', $result->phone);
        $this->assertSame('Opel', $result->vehicleMake);
        $this->assertSame('Insignia', $result->vehicleModel);
        $this->assertSame('AA1234BB', $result->vehiclePlate);
        $this->assertSame('tomorrow morning', $result->preferredTimeText);
        $this->assertSame('Check engine light came on.', $result->problemSummary);
        $this->assertNull($result->missingNextField);
        $this->assertSame(0.85, $result->confidence);
    }

    public function test_missing_optional_extraction_values_become_null(): void
    {
        $result = $this->mapper()->map([]);

        $this->assertNull($result->phone);
        $this->assertNull($result->vehicleMake);
        $this->assertNull($result->vehicleModel);
        $this->assertNull($result->vehiclePlate);
        $this->assertNull($result->preferredTimeText);
        $this->assertNull($result->problemSummary);
        $this->assertSame(MissingIntakeField::Phone->value, $result->missingNextField);
        $this->assertNull($result->confidence);
    }

    public function test_invalid_vehicle_payload_does_not_crash(): void
    {
        $result = $this->mapper()->map([
            'phone' => '380501112233',
            'vehicle' => 'Opel Insignia',
            'missing_next_field' => 'vehicle',
        ]);

        $this->assertSame('380501112233', $result->phone);
        $this->assertNull($result->vehicleMake);
        $this->assertNull($result->vehicleModel);
        $this->assertNull($result->vehiclePlate);
        $this->assertNull($result->missingNextField);
    }

    public function test_missing_next_field_is_resolved_from_mapped_fields_not_model_output(): void
    {
        $result = $this->mapper()->map([
            'phone' => null,
            'vehicle' => [
                'make' => 'Opel',
                'model' => 'Insignia',
                'plate' => null,
            ],
            'preferred_time_text' => 'tomorrow morning',
            'missing_next_field' => null,
        ]);

        $this->assertSame(MissingIntakeField::Phone->value, $result->missingNextField);
    }

    public function test_non_string_text_fields_are_treated_as_missing(): void
    {
        $result = $this->mapper()->map([
            'phone' => ['+380501112233'],
            'vehicle' => [
                'make' => 123,
                'model' => false,
                'plate' => ['AA1234BB'],
            ],
            'preferred_time_text' => ['tomorrow morning'],
            'problem_summary' => ['Check engine light came on.'],
        ]);

        $this->assertNull($result->phone);
        $this->assertNull($result->vehicleMake);
        $this->assertNull($result->vehicleModel);
        $this->assertNull($result->vehiclePlate);
        $this->assertNull($result->preferredTimeText);
        $this->assertNull($result->problemSummary);
        $this->assertSame(MissingIntakeField::Phone->value, $result->missingNextField);
    }

    public function test_invalid_or_out_of_range_confidence_does_not_crash(): void
    {
        $missingConfidence = $this->mapper()->map([
            'confidence' => 'high',
        ]);

        $negativeConfidence = $this->mapper()->map([
            'confidence' => -0.5,
        ]);

        $highConfidence = $this->mapper()->map([
            'confidence' => 1.5,
        ]);

        $this->assertNull($missingConfidence->confidence);
        $this->assertSame(0.0, $negativeConfidence->confidence);
        $this->assertSame(1.0, $highConfidence->confidence);
    }

    public function test_mapper_does_not_invent_forbidden_fields(): void
    {
        $result = $this->mapper()->map([
            'diagnosis' => 'Oxygen sensor failure',
            'repair_recommendations' => ['Replace oxygen sensor'],
            'price_estimate' => '100 EUR',
            'availability_promise' => 'Tomorrow at 10:00',
        ]);

        $fields = array_keys($result->toArray());

        $this->assertNotContains('diagnosis', $fields);
        $this->assertNotContains('repair_recommendations', $fields);
        $this->assertNotContains('price_estimate', $fields);
        $this->assertNotContains('availability_promise', $fields);
    }

    private function mapper(): OpenAiIntakeExtractionResultMapper
    {
        return new OpenAiIntakeExtractionResultMapper;
    }
}
