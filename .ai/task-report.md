# Task Report

## Goal

Implement P0 hardening from project investigation: rate limiting + honeypot on public intake, real OpenAI-backed `LlmIntakeExtractor` with manual fallback, repo hygiene (yarn.lock removal).

## Files Changed

- `routes/web.php` - adds `throttle:10,1` middleware to public intake and public booking POST routes.
- `app/Http/Requests/StorePublicIntakeRequest.php` - adds `website` honeypot field with `prohibited` rule.
- `resources/js/pages/PublicIntake.vue` - adds hidden honeypot input (`tabindex=-1`, `aria-hidden`, `class="hidden"`) bound to the form.
- `app/Support/Intake/LlmIntakeExtractor.php` - implements real extraction: OpenAI chat completions call with `json_object` response format, temperature 0, 4000-char message cap, configurable timeout; on any failure logs a warning and delegates to `ManualFallbackIntakeExtractor`.
- `app/Providers/AppServiceProvider.php` - conditional binding: `LlmIntakeExtractor` when `services.openai.api_key` is set, otherwise `ManualFallbackIntakeExtractor`.
- `config/services.php` - adds `openai` block (api_key, intake_model default `gpt-4o-mini`, base_url, timeout).
- `.env.example` - documents `OPENAI_*` variables; empty key means manual fallback.
- `tests/Feature/PublicIntakeSubmissionTest.php` - adds honeypot (filled rejected / empty passes) and rate-limit (11th request 429) tests.
- `tests/Feature/LlmIntakeExtractionTest.php` - new: binding selection by config, successful response mapping, fallback on HTTP 500, fallback on invalid JSON, message truncation (all via `Http::fake`).
- `yarn.lock` - removed (npm + package-lock.json is the package manager).

## Implementation Summary

Public intake endpoints are now throttled (10/min per IP) and protected by a honeypot field, closing the spam/LLM-cost-abuse gap before real traffic. The chat-first product core — LLM extraction — is now live behind the existing `IntakeExtractorInterface` boundary: no controller or action changed. The extractor is resilient by design: any HTTP error, timeout, or malformed model output falls back to the regex-based manual extractor, so intake never fails for the customer.

## Architecture Decisions

- Fallback lives inside `LlmIntakeExtractor` as an injected `ManualFallbackIntakeExtractor` dependency rather than a decorator/composite class — one place decides, controllers/actions stay untouched, and the binding stays simple.
- Config under `services.openai` (Laravel convention for third-party credentials) instead of a new config file.
- Honeypot uses `prohibited` rule (passes on empty string, fails on filled) — simplest server-side check; silent-drop was rejected as it would put logic in the controller.
- `mb_substr` cap at 4000 chars bounds token cost per request (validation already caps input at 5000).

## Tradeoffs

- Throttle is per-IP via the default limiter; a distributed botnet bypasses it. Acceptable for MVP.
- Fallback swallows all `Throwable` from the LLM path — deliberate availability-over-visibility choice; failures are logged as warnings.
- `docs/product/Archive.zip` left in repo — not clearly junk, needs owner confirmation.

## Tests

- `php artisan test` — 144 passed (1183 assertions).
- New coverage: `tests/Feature/LlmIntakeExtractionTest.php` (6 tests), 3 new tests in `PublicIntakeSubmissionTest.php`.

## Risks

- Real OpenAI behavior (latency, occasional non-JSON output) only simulated via `Http::fake`; first production use should be watched in logs (`LLM intake extraction failed` warning).
- Rate limit of 10/min is a guess; tune after observing real traffic.

## Follow Ups

- P1: larastan, FormRequest authorization pattern decision, `MarkRepairOrderEstimatedAction` → `canTransitionTo()`, `EstimateStatus` transitions + own translation namespace.
- P2: Vitest for frontend, pre-commit hooks, money-math edge-case unit tests, follow-up-question UI on intake.
- Confirm whether `docs/product/Archive.zip` can be deleted.
- Consider named rate limiter (`RateLimiter::for('public-intake')`) if limits diverge per route.
