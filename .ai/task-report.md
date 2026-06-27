# Task Report

## Goal

Continue the chat-first public intake MVP with one small backend validation slice: reject whitespace-only intake messages before creating a `BookingRequest`.

## Files Changed

- `app/Http/Requests/StorePublicIntakeRequest.php`
- `tests/Feature/PublicIntakeSubmissionTest.php`
- `.ai/task-report.md`

## Implementation Summary

Added a FormRequest validation rule that fails when `message` contains only whitespace.

Added a focused feature test proving a whitespace-only public intake submission redirects back with a validation error and creates no `booking_requests` row.

## Architecture Decisions

Kept validation in `StorePublicIntakeRequest` because rejecting invalid request input belongs at the HTTP validation boundary.

Left `PublicIntakeController` thin and unchanged: it still delegates the accepted message to `SubmitIntakeRequestAction`.

Left `SubmitIntakeRequestAction` unchanged so valid messages continue to be stored exactly as submitted in both `original_message` and `problem_description`.

## Tradeoffs

Used a small closure rule instead of trimming input during preparation. This avoids mutating legitimate user text and preserves the existing test expectation that original message content remains unchanged.

Did not introduce reusable validation objects because this rule is currently used in one request only.

## Tests

Ran:

```txt
php artisan test tests/Feature/PublicIntakeSubmissionTest.php
```

Result:

```txt
6 passed, 42 assertions
```

## Risks

The validation message is intentionally simple and customer-facing. If localization is added later, it should move into Laravel language files.

## Follow Ups

Next recommended step: remove the stale `workshops` prop/query from the home route or add a small assertion that the public landing no longer exposes workshop-specific booking entry points.

Suggested learning note if useful later:

```txt
docs/learning/laravel-formrequest-custom-validation.md
```
