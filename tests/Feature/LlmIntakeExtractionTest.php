<?php

namespace Tests\Feature;

use App\Support\Intake\IntakeExtractorInterface;
use App\Support\Intake\LlmIntakeExtractor;
use App\Support\Intake\ManualFallbackIntakeExtractor;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LlmIntakeExtractionTest extends TestCase
{
    public function test_llm_extractor_is_bound_when_openai_api_key_is_configured(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        $this->assertInstanceOf(LlmIntakeExtractor::class, $this->app->make(IntakeExtractorInterface::class));
    }

    public function test_manual_fallback_extractor_is_bound_without_openai_api_key(): void
    {
        config(['services.openai.api_key' => null]);

        $this->assertInstanceOf(ManualFallbackIntakeExtractor::class, $this->app->make(IntakeExtractorInterface::class));
    }

    public function test_llm_extractor_maps_successful_openai_response(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'phone' => '380501112233',
                                'vehicle' => [
                                    'make' => 'Opel',
                                    'model' => 'Insignia',
                                    'plate' => null,
                                ],
                                'preferred_time_text' => 'tomorrow morning',
                                'problem_summary' => 'Check engine light came on.',
                                'missing_next_field' => null,
                                'confidence' => 0.9,
                            ]),
                        ],
                    ],
                ],
            ]),
        ]);

        $result = $this->app->make(IntakeExtractorInterface::class)
            ->extract('Opel Insignia, check engine light came on. Call 050 111 22 33, tomorrow morning?');

        $this->assertSame('380501112233', $result->phone);
        $this->assertSame('Opel', $result->vehicleMake);
        $this->assertSame('Insignia', $result->vehicleModel);
        $this->assertSame('tomorrow morning', $result->preferredTimeText);
        $this->assertSame('Check engine light came on.', $result->problemSummary);
        $this->assertNull($result->missingNextField);
        $this->assertSame(0.9, $result->confidence);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.openai.com/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && $request['model'] === 'gpt-4o-mini'
                && $request['response_format'] === ['type' => 'json_object'];
        });
    }

    public function test_llm_extractor_falls_back_to_manual_extraction_on_http_failure(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => Http::response(['error' => 'overloaded'], 500),
        ]);

        $message = 'Honda Civic makes noise. Call +1 (555) 123-4567.';
        $result = $this->app->make(IntakeExtractorInterface::class)->extract($message);

        $this->assertSame('15551234567', $result->phone);
        $this->assertNull($result->vehicleMake);
        $this->assertSame($message, $result->problemSummary);
        $this->assertSame(0.2, $result->confidence);
    }

    public function test_llm_extractor_falls_back_when_response_is_not_valid_json(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'not json at all']],
                ],
            ]),
        ]);

        $message = 'Brakes squeak when stopping.';
        $result = $this->app->make(IntakeExtractorInterface::class)->extract($message);

        $this->assertNull($result->phone);
        $this->assertSame($message, $result->problemSummary);
        $this->assertSame(0.0, $result->confidence);
    }

    public function test_llm_extractor_truncates_long_messages_before_sending(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode(['problem_summary' => 'Long message.'])]],
                ],
            ]),
        ]);

        $this->app->make(IntakeExtractorInterface::class)
            ->extract(str_repeat('a', LlmIntakeExtractor::MAX_MESSAGE_CHARACTERS + 500));

        Http::assertSent(function (Request $request): bool {
            return mb_strlen($request['messages'][1]['content']) === LlmIntakeExtractor::MAX_MESSAGE_CHARACTERS;
        });
    }
}
