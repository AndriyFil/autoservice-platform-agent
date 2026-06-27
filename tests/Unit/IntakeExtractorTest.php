<?php

namespace Tests\Unit;

use App\Support\Intake\IntakeExtractionResult;
use App\Support\Intake\LlmIntakeExtractor;
use App\Support\Intake\ManualFallbackIntakeExtractor;
use App\Support\PhoneNormalizer;
use PHPUnit\Framework\TestCase;

class IntakeExtractorTest extends TestCase
{
    public function test_fallback_does_not_fake_vehicle_extraction(): void
    {
        $data = $this->fallback()->extract(
            'Opel Insignia, check engine light came on. Call +38 (050) 111-22-33 please.'
        );

        $this->assertNull($data->vehicleMake);
        $this->assertNull($data->vehicleModel);
        $this->assertNull($data->vehicleYear);
        $this->assertContains('vehicle', $data->missingFields);
    }

    public function test_extractor_contract_has_no_diagnosis_or_recommendation_fields(): void
    {
        $fields = array_keys($this->emptyResult()->toArray());

        $this->assertSame([
            'vehicle_make',
            'vehicle_model',
            'vehicle_year',
            'issue_text',
            'customer_suspected_cause',
            'preferred_time_text',
            'phone',
            'missing_fields',
            'confidence',
        ], $fields);

        $this->assertNotContains('diagnosis', $fields);
        $this->assertNotContains('repair_recommendations', $fields);
        $this->assertNotContains('price_estimate', $fields);
        $this->assertNotContains('availability_promise', $fields);
    }

    public function test_missing_fields_contains_phone_and_vehicle_when_absent(): void
    {
        $data = $this->fallback()->extract('Something is wrong with my car.');

        $this->assertSame(['vehicle', 'phone'], $data->missingFields);
    }

    public function test_phone_can_still_be_normalized_safely_when_provided(): void
    {
        $data = $this->fallback()->extract('Please call me at +38 (050) 111-22-33.');

        $this->assertSame('380501112233', $data->phone);
        $this->assertNotContains('phone', $data->missingFields);
        $this->assertContains('vehicle', $data->missingFields);
    }

    public function test_llm_prompt_spec_does_not_define_forbidden_output_fields(): void
    {
        $this->assertStringNotContainsString('diagnosis:', LlmIntakeExtractor::PROMPT_SPEC);
        $this->assertStringNotContainsString('repair_recommendations:', LlmIntakeExtractor::PROMPT_SPEC);
        $this->assertStringNotContainsString('price_estimate:', LlmIntakeExtractor::PROMPT_SPEC);
        $this->assertStringNotContainsString('availability_promise:', LlmIntakeExtractor::PROMPT_SPEC);
    }

    private function fallback(): ManualFallbackIntakeExtractor
    {
        return new ManualFallbackIntakeExtractor(new PhoneNormalizer());
    }

    private function emptyResult(): IntakeExtractionResult
    {
        return new IntakeExtractionResult(
            vehicleMake: null,
            vehicleModel: null,
            vehicleYear: null,
            issueText: null,
            customerSuspectedCause: null,
            preferredTimeText: null,
            phone: null,
            missingFields: [],
            confidence: 0.0,
        );
    }
}
