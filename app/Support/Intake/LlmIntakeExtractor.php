<?php

namespace App\Support\Intake;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class LlmIntakeExtractor implements IntakeExtractorInterface
{
    public const MAX_MESSAGE_CHARACTERS = 4000;

    public const PROMPT_SPEC = <<<'PROMPT'
You extract structured intake data for an auto service advisor.

Return only JSON with these keys:
- phone: string|null
- vehicle: object|null with these keys:
  - make: string|null
  - model: string|null
  - plate: string|null
- preferred_time_text: string|null
- problem_summary: string|null
- missing_next_field: "phone"|null
- confidence: number from 0 to 1|null

Rules:
- Preserve the customer's meaning without diagnosing the vehicle.
- problem_summary must summarize only what the customer wrote.
- The only required customer follow-up field is phone. Vehicle and preferred time are optional enrichment.
- If phone is missing, set missing_next_field to "phone"; otherwise set it to null.
- Server-side rules will re-check missing_next_field after mapping.
- Do not estimate prices.
- Do not recommend repairs.
- Do not promise appointment availability.
- Do not claim a confirmed cause.
PROMPT;

    public function __construct(
        private readonly OpenAiIntakeExtractionResultMapper $resultMapper,
        private readonly ManualFallbackIntakeExtractor $fallbackExtractor,
        private readonly string $apiKey,
        private readonly string $model,
        private readonly string $baseUrl,
        private readonly int $timeoutSeconds,
    ) {}

    public function extract(string $message): IntakeExtractionResult
    {
        try {
            return $this->resultMapper->map($this->requestExtraction($message));
        } catch (Throwable $exception) {
            Log::warning('LLM intake extraction failed, using manual fallback.', [
                'error' => $exception->getMessage(),
            ]);

            return $this->fallbackExtractor->extract($message);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function requestExtraction(string $message): array
    {
        $response = Http::withToken($this->apiKey)
            ->timeout($this->timeoutSeconds)
            ->post("{$this->baseUrl}/chat/completions", [
                'model' => $this->model,
                'temperature' => 0,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => self::PROMPT_SPEC],
                    ['role' => 'user', 'content' => mb_substr($message, 0, self::MAX_MESSAGE_CHARACTERS)],
                ],
            ])
            ->throw();

        $content = $response->json('choices.0.message.content');
        $data = is_string($content) ? json_decode($content, true) : null;

        if (! is_array($data)) {
            throw new RuntimeException('OpenAI intake extraction returned invalid JSON.');
        }

        return $data;
    }
}
