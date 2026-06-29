<?php

namespace App\Support\Intake;

use LogicException;

class LlmIntakeExtractor implements IntakeExtractorInterface
{
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
- missing_next_field: "phone"|"vehicle"|"preferred_time"|null
- confidence: number from 0 to 1|null

Rules:
- Preserve the customer's meaning without diagnosing the vehicle.
- problem_summary must summarize only what the customer wrote.
- If required details are missing, set missing_next_field using this priority: phone, vehicle, preferred_time.
- Server-side rules will re-check missing_next_field after mapping.
- Do not estimate prices.
- Do not recommend repairs.
- Do not promise appointment availability.
- Do not claim a confirmed cause.
PROMPT;

    public function extract(string $message): IntakeExtractionResult
    {
        throw new LogicException('LLM intake extraction is not configured yet.');
    }
}
