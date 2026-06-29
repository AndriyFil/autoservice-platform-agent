# Task Report

## Goal

Build the safe Intake MVP v1 foundation:

- public chat-first request creates an unassigned `submitted` `BookingRequest`
- safe extraction boundary remains behind `IntakeExtractorInterface`
- safe phone extraction may be persisted
- central admin queue foundation exists without exposing global unassigned requests to workshop dashboards

## Files Changed

- `app/Actions/BookingRequests/SubmitIntakeRequestAction.php`
- `app/Enums/MissingIntakeField.php`
- `app/Support/Intake/MissingNextIntakeFieldRule.php`
- `app/Support/Intake/MissingPhoneIntakeFieldRule.php`
- `app/Support/Intake/MissingVehicleIntakeFieldRule.php`
- `app/Support/Intake/MissingPreferredTimeIntakeFieldRule.php`
- `app/Support/Intake/MissingNextIntakeFieldResolver.php`
- `app/Queries/Admin/UnassignedIntakeRequestsQuery.php`
- `tests/Feature/PublicIntakeSubmissionTest.php`
- `tests/Feature/DashboardTest.php`
- `tests/Feature/UnassignedIntakeRequestsQueryTest.php`
- `tests/Unit/IntakeExtractorTest.php`
- `tests/Unit/OpenAiIntakeExtractionResultMapperTest.php`
- `docs/product/admin-workshop-inbox.md`
- `docs/product/business-rules.md`
- `docs/product/domain-model.md`
- `docs/product/mvp-scope.md`
- `.ai/lessons/autoservice.md`
- `.ai/task-report.md`

## Implementation Summary

Ran coordinated workstreams:

- Architect: decided not to expose `workshop_id = null` intake records through the active-workshop dashboard because no platform-admin authorization model exists.
- Backend: implemented the safe backend foundation and tests.
- Frontend: inspected the dashboard path; no frontend queue was implemented because backend visibility is intentionally not exposed yet.
- Product/docs: updated product docs to reflect Central Admin Queue as the MVP routing strategy.
- Reviewer: checked likely permission leaks and confirmed the key risk was global unassigned request visibility.

Public intake now persists a safely extracted phone number from the extractor result into `customer_phone`, while still preserving:

- `workshop_id = null`
- `customer_id = null`
- `vehicle_id = null`
- `created_by_user_id = null`
- exact `original_message`
- `problem_description` copied from the original message
- `status = submitted`

Added `App\Queries\Admin\UnassignedIntakeRequestsQuery` as a backend-only foundation for a future central admin queue. It returns only `BookingRequest` records where:

- `workshop_id` is `null`
- `status` is `submitted`

The query returns safe read-model fields: received time, original message, problem summary, phone, no vehicle, missing-next-field label, and `Needs review` status label.

Added tests proving the query filters correctly, orders oldest first, applies a limit, preserves original message text, and uses safe missing-field labels.

Added `MissingIntakeField` enum and updated missing-field rules, resolver tests, mapper tests, and `UnassignedIntakeRequestsQuery` to use enum values/labels instead of hardcoded field strings.

Added a dashboard protection test proving unassigned submitted public intake requests do not appear in the normal active-workshop dashboard and no `unassignedIntakeRequests` dashboard prop is exposed.

Updated product docs to describe chat-first intake, central admin queue routing, assignment as future work, and safety boundaries.

## Architecture Decisions

Did not add an admin route, dashboard prop, or Vue component for unassigned requests. The existing authorization model only has workshop-scoped `owner` and `staff`, so exposing global unassigned requests would risk cross-workshop leakage.

Kept controllers unchanged and thin. The new central queue foundation is a Query class, not inline controller SQL.

Kept `BookingRequest` as the intake aggregate. No `Chat`, `Conversation`, `AiThread`, workflow engine, queue, CQRS, event sourcing, or microservice was introduced.

Kept extraction behind `IntakeExtractorInterface`. No OpenAI API call, API key, HTTP client, SDK setup, or container provider binding was added.

Did not create `Customer` or `Vehicle` from unassigned public intake. Those remain future assignment/enrichment decisions.

## Tradeoffs

Persisting the extracted phone gives the future central admin queue a useful contact hint while avoiding fake vehicle parsing or customer creation.

The central queue query is backend-only for now. This is less visible as a product feature, but it avoids leaking all public intake requests to normal workshop dashboards before a platform-admin permission model exists.

The query labels missing fields for display, but still delegates priority to `MissingNextIntakeFieldResolver`; it does not duplicate the phone-before-vehicle-before-time rule.

Missing intake field values and labels are centralized in `MissingIntakeField` to avoid string drift between rules, queries, and tests.

The docs now mention central admin assignment before the role exists. This intentionally records the product direction while keeping implementation blocked until authorization is designed.

## Tests

Ran:

```txt
php artisan test tests/Unit/IntakeExtractorTest.php
php artisan test tests/Unit/OpenAiIntakeExtractionResultMapperTest.php
php artisan test tests/Feature/PublicIntakeSubmissionTest.php
php artisan test tests/Feature/DashboardTest.php
php artisan test tests/Feature/UnassignedIntakeRequestsQueryTest.php
php -l app/Enums/MissingIntakeField.php
php -l app/Queries/Admin/UnassignedIntakeRequestsQuery.php
php -l tests/Feature/UnassignedIntakeRequestsQueryTest.php
git diff --check -- app/Actions/BookingRequests/SubmitIntakeRequestAction.php app/Enums/MissingIntakeField.php app/Support/Intake/MissingNextIntakeFieldRule.php app/Support/Intake/MissingPhoneIntakeFieldRule.php app/Support/Intake/MissingVehicleIntakeFieldRule.php app/Support/Intake/MissingPreferredTimeIntakeFieldRule.php app/Support/Intake/MissingNextIntakeFieldResolver.php app/Queries/Admin/UnassignedIntakeRequestsQuery.php tests/Feature/PublicIntakeSubmissionTest.php tests/Feature/DashboardTest.php tests/Feature/UnassignedIntakeRequestsQueryTest.php tests/Unit/IntakeExtractorTest.php tests/Unit/OpenAiIntakeExtractionResultMapperTest.php docs/product/admin-workshop-inbox.md docs/product/mvp-scope.md docs/product/business-rules.md docs/product/domain-model.md
```

Results:

```txt
Tests\Unit\IntakeExtractorTest: 10 passed, 30 assertions
Tests\Unit\OpenAiIntakeExtractionResultMapperTest: 7 passed, 36 assertions
Tests\Feature\PublicIntakeSubmissionTest: 7 passed, 66 assertions
Tests\Feature\DashboardTest: 9 passed, 152 assertions
Tests\Feature\UnassignedIntakeRequestsQueryTest: 3 passed, 18 assertions
PHP syntax checks: passed
git diff --check: passed
```

## Risks

There is still no platform-admin role or permission model. Until that exists, the central admin queue must remain unexposed.

Assignment from unassigned submitted intake to a workshop is not implemented. The future assignment action needs explicit authorization and status-transition rules.

The older workshop-specific public booking routes still exist. They are documented as separate from the chat-first landing intake and are not linked from the landing page.

The worktree still contains unrelated pre-existing changes in `.ai/lessons/autoservice.md` and unrelated untracked zip files.

## Follow Ups

Next recommended milestone:

1. Define a platform-admin role or permission model for central queue visibility.
2. Expose `UnassignedIntakeRequestsQuery` through an authorized admin route/page.
3. Add a focused `AssignIntakeRequestToWorkshopAction` that routes one submitted unassigned request to one workshop and changes status to `new`.
4. Decide when customer/vehicle records are created or linked during assignment.
5. Add a passive frontend central queue UI only after backend authorization exists.
