# Task Report

## Goal

Normalize and lock the chat-first intake `missingNextField` priority so phone is requested before vehicle when both are missing.

## Files Changed

- `tests/Unit/IntakeExtractorTest.php`
- `.ai/task-report.md`

## Implementation Summary

Verified the current intake extraction boundary:

- `ManualFallbackIntakeExtractor` still extracts only obvious phone numbers.
- `ManualFallbackIntakeExtractor` still keeps the original customer message as the problem summary.
- `MissingNextIntakeFieldResolver` already resolves fields in the expected order: phone, vehicle, preferred time, then null.

Added a focused regression test proving `MissingNextIntakeFieldResolver` returns `phone` when phone, vehicle, and preferred time are all missing. This locks the product rule that phone is the primary customer identity for MVP intake.

No OpenAI calls, chat entities, conversation entities, diagnosis, pricing, repair recommendations, or availability promises were added.

## Architecture Decisions

Kept the priority rule in the shared intake resolver instead of duplicating it inside the fallback extractor. This keeps manual fallback and future AI-backed extraction aligned behind one small boundary.

Did not add a new abstraction. The existing resolver already expresses the protected variation point clearly enough for this slice.

## Tradeoffs

This change is test-only because the current resolver implementation already matches the desired priority. The tradeoff is that this iteration strengthens regression coverage rather than changing runtime behavior.

## Tests

Ran:

```txt
php artisan test tests/Unit/IntakeExtractorTest.php
php artisan test tests/Feature/PublicIntakeSubmissionTest.php
git diff --check -- tests/Unit/IntakeExtractorTest.php
```

Result:

```txt
Tests\Unit\IntakeExtractorTest: 9 passed, 22 assertions
Tests\Feature\PublicIntakeSubmissionTest: 7 passed, 61 assertions
git diff --check: passed
```

## Risks

No runtime risk found for this slice because application code did not need to change.

The worktree still contains pre-existing modified and untracked files outside this small iteration.

## Follow Ups

Next recommended step: when OpenAI extraction is implemented, route the parsed result through `MissingNextIntakeFieldResolver` so AI-backed and manual fallback extraction share the same phone-first follow-up priority.
