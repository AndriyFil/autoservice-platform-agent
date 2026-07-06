<?php

namespace Tests\Unit;

use App\Enums\MissingIntakeField;
use App\Support\Intake\IntakeExtractionResult;
use App\Support\Intake\LlmIntakeExtractor;
use App\Support\Intake\ManualFallbackIntakeExtractor;
use App\Support\Intake\MissingNextIntakeFieldResolver;
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
        $this->assertNull($data->vehiclePlate);
        $this->assertNull($data->missingNextField);
    }

    public function test_extractor_contract_has_no_diagnosis_or_recommendation_fields(): void
    {
        $fields = array_keys($this->emptyResult()->toArray());

        $this->assertSame([
            'phone',
            'vehicle_make',
            'vehicle_model',
            'vehicle_plate',
            'preferred_time_text',
            'problem_summary',
            'missing_next_field',
            'confidence',
        ], $fields);

        $this->assertNotContains('diagnosis', $fields);
        $this->assertNotContains('repair_recommendations', $fields);
        $this->assertNotContains('price_estimate', $fields);
        $this->assertNotContains('availability_promise', $fields);
    }

    public function test_missing_next_field_is_phone_when_phone_is_absent(): void
    {
        $data = $this->fallback()->extract('Something is wrong with my car.');

        $this->assertNull($data->phone);
        $this->assertSame(MissingIntakeField::Phone->value, $data->missingNextField);
    }

    public function test_missing_next_field_resolver_requires_only_phone(): void
    {
        $field = (new MissingNextIntakeFieldResolver)->resolve(
            phone: null,
            vehicleMake: null,
            vehicleModel: null,
            vehiclePlate: null,
            preferredTimeText: null,
        );

        $this->assertSame(MissingIntakeField::Phone, $field);
    }

    public function test_missing_next_field_is_null_when_phone_exists_but_vehicle_is_missing(): void
    {
        $data = $this->fallback()->extract('Something is wrong with my car. Call +38 (050) 111-22-33.');

        $this->assertSame('+380501112233', $data->phone);
        $this->assertNull($data->missingNextField);
    }

    public function test_phone_can_still_be_normalized_safely_when_provided(): void
    {
        $data = $this->fallback()->extract('Please call me at +38 (050) 111-22-33.');

        $this->assertSame('+380501112233', $data->phone);
        $this->assertNull($data->missingNextField);
    }

    public function test_missing_next_field_resolver_does_not_require_preferred_time(): void
    {
        $field = (new MissingNextIntakeFieldResolver)->resolve(
            phone: '380501112233',
            vehicleMake: 'Opel',
            vehicleModel: 'Insignia',
            vehiclePlate: null,
            preferredTimeText: null,
        );

        $this->assertNull($field);
    }

    public function test_missing_next_field_resolver_returns_null_when_enough_information_exists(): void
    {
        $field = (new MissingNextIntakeFieldResolver)->resolve(
            phone: '380501112233',
            vehicleMake: 'Opel',
            vehicleModel: 'Insignia',
            vehiclePlate: null,
            preferredTimeText: 'tomorrow morning',
        );

        $this->assertNull($field);
    }

    public function test_llm_prompt_spec_does_not_define_forbidden_output_fields(): void
    {
        $this->assertStringNotContainsString('diagnosis:', LlmIntakeExtractor::PROMPT_SPEC);
        $this->assertStringNotContainsString('repair_recommendations:', LlmIntakeExtractor::PROMPT_SPEC);
        $this->assertStringNotContainsString('price_estimate:', LlmIntakeExtractor::PROMPT_SPEC);
        $this->assertStringNotContainsString('availability_promise:', LlmIntakeExtractor::PROMPT_SPEC);
    }

    public function test_llm_prompt_spec_uses_nested_vehicle_schema(): void
    {
        $this->assertStringContainsString('- vehicle: object|null with these keys:', LlmIntakeExtractor::PROMPT_SPEC);
        $this->assertStringContainsString('  - make: string|null', LlmIntakeExtractor::PROMPT_SPEC);
        $this->assertStringContainsString('  - model: string|null', LlmIntakeExtractor::PROMPT_SPEC);
        $this->assertStringContainsString('  - plate: string|null', LlmIntakeExtractor::PROMPT_SPEC);
        $this->assertStringContainsString('- missing_next_field: "phone"|null', LlmIntakeExtractor::PROMPT_SPEC);
        $this->assertStringNotContainsString('"vehicle"|"preferred_time"', LlmIntakeExtractor::PROMPT_SPEC);
        $this->assertStringNotContainsString('phone, vehicle, preferred_time', LlmIntakeExtractor::PROMPT_SPEC);
        $this->assertStringNotContainsString('vehicle_make:', LlmIntakeExtractor::PROMPT_SPEC);
        $this->assertStringNotContainsString('vehicle_model:', LlmIntakeExtractor::PROMPT_SPEC);
        $this->assertStringNotContainsString('vehicle_plate:', LlmIntakeExtractor::PROMPT_SPEC);
    }

    private function fallback(): ManualFallbackIntakeExtractor
    {
        return new ManualFallbackIntakeExtractor(
            new MissingNextIntakeFieldResolver,
        );
    }

    private function emptyResult(): IntakeExtractionResult
    {
        return new IntakeExtractionResult(
            phone: null,
            vehicleMake: null,
            vehicleModel: null,
            vehiclePlate: null,
            preferredTimeText: null,
            problemSummary: null,
            missingNextField: null,
            confidence: 0.0,
        );
    }
}
