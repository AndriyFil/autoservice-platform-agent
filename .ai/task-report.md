# Task Report

## Goal

Add canonical phone normalization so Customer matching uses `phone_normalized` inside the active workshop and does not create duplicates for the same real phone entered in different formats.

## Files Changed

- `app/Support/Phone.php` - implements a constructor-based phone helper that holds the raw phone value and exposes canonical Ukrainian normalization, plus a legacy digits-only helper for the existing `normalized_phone` column.
- `app/Models/Customer.php` - fills/recalculates `phone_normalized` when `phone` changes while keeping legacy `normalized_phone` populated for existing code paths.
- `app/Models/BookingRequest.php` - fills/recalculates `customer_phone_normalized` when `customer_phone` changes.
- `app/Actions/BookingRequests/ResolveBookingRequestCustomerAction.php` - matches customers by `workshop_id` and `phone_normalized`.
- `app/Actions/BookingRequests/CreatePublicBookingRequestAction.php` - stores `customer_phone_normalized` on public booking requests.
- `app/Actions/BookingRequests/SubmitIntakeRequestAction.php` - stores raw public intake phone plus `customer_phone_normalized`.
- `app/Http/Requests/StorePublicIntakeRequest.php` - returns the submitted display phone instead of the normalized value.
- `app/Actions/RepairOrders/CreateRepairOrderFromBookingRequestAction.php` - reuses or creates customers by booking request `customer_phone_normalized`.
- `app/Queries/Dashboard/DashboardRepairOrderFormQuery.php` - preselects existing customers by normalized booking phone and exposes `phoneNormalized` for picker search.
- `resources/js/components/repair-orders/RepairOrderCustomerSelect.vue` - normalizes typed phone search input while continuing to display `phone`.
- `resources/js/components/repair-orders/types.ts` - adds `phoneNormalized` to repair-order customer options.
- `database/migrations/2026_07_06_000001_add_phone_normalized_columns.php` - adds/backfills normalized phone columns and conditionally creates the customer unique index.
- `database/factories/CustomerFactory.php` and `database/factories/BookingRequestFactory.php` - generate normalized phone fields in test data.
- `database/seeders/DatabaseSeeder.php` - seeds customers through `phone_normalized`.
- `tests/Unit/PhoneTest.php` - covers Ukrainian variants and imperfect input.
- `tests/Feature/PublicIntakeSubmissionTest.php` - verifies public intake stores display phone and normalized phone separately.
- `tests/Feature/PublicBookingRequestFlowTest.php` - verifies Ukrainian variants reuse one customer inside a workshop and same phone can exist in another workshop.
- `tests/Feature/RepairOrderManagementTest.php` - verifies repair-order creation from booking request reuses customer by `phone_normalized`.
- `tests/Feature/CustomerManagementTest.php` - verifies customer phone updates recalculate `phone_normalized`.
- `tests/Unit/IntakeExtractorTest.php` and `tests/Feature/LlmIntakeExtractionTest.php` - updates fallback extraction expectations to canonical phone output.

## Implementation Summary

- Canonical `phone_normalized` rules:
  - remove spaces, dashes, and parentheses
  - preserve a leading `+`
  - `0XXXXXXXXX` becomes `+380XXXXXXXXX`
  - `380XXXXXXXXX` becomes `+380XXXXXXXXX`
  - `+380XXXXXXXXX` stays `+380XXXXXXXXX`
  - imperfect input falls back to cleaned `+digits` or digits without throwing
- Display phones remain stored in `phone` and `customer_phone`.
- Customer matching now uses `workshop_id + phone_normalized`, not raw phone and not global phone matching.
- Public intake and public booking requests store `customer_phone_normalized`.
- Repair-order creation from booking request finds/creates customers by `customer_phone_normalized`.
- Customer picker phone search compares normalized query text to `phoneNormalized` while displaying raw phone.

## Architecture Decisions

- Replaced the stateless `PhoneNormalizer` service with a focused `App\Support\Phone` Pure Fabrication/value-style helper, so the raw phone is explicit constructor state and normalization is behavior on that value.
- Added model-level recalculation as a persistence safeguard because customers and booking requests are created from several actions, factories, and tests.
- Kept legacy `normalized_phone` populated to avoid breaking existing code/data while new matching moves to `phone_normalized`.
- Kept workshop isolation in every customer lookup by requiring `workshop_id`.

## Tradeoffs

- Both `normalized_phone` and `phone_normalized` exist for now. `phone_normalized` is the new canonical matching field; `normalized_phone` remains for compatibility with existing schema/tests.
- The migration adds `unique(workshop_id, phone_normalized)` only when current data has no duplicates. If duplicates exist, it leaves a non-unique lookup index so data is not deleted or merged automatically.

## Tests

- `php artisan test tests/Feature/PublicIntakeSubmissionTest.php` - passed, 16 tests / 146 assertions.
- `php artisan test tests/Feature/RepairOrderManagementTest.php` - passed, 38 tests / 445 assertions.
- `php artisan test tests/Feature/CustomerManagementTest.php` - passed, 7 tests / 106 assertions.
- `php artisan test tests/Feature/PublicBookingRequestFlowTest.php` - passed, 10 tests / 126 assertions.
- `php artisan test` - passed, 201 tests / 1632 assertions.
- `composer analyse` - first sandbox run failed with TCP listener `EPERM`; rerun with approved escalation passed with no errors.

## Risks

- I did not run migrations against a persistent development/production database, so no existing live duplicate customer groups were inspected directly.
- The migration will detect duplicates at migration time. If any duplicate `(workshop_id, phone_normalized)` groups exist, it will not force-delete or merge data and will create a non-unique index instead.
- The repository had many pre-existing dirty worktree changes before this task; they were not reverted.

## Follow Ups

- If migration fallback is triggered on real data, run a focused duplicate cleanup/report task before adding the unique constraint.
- Consider a future cleanup to remove the legacy `normalized_phone` column after all code and existing data are fully migrated to `phone_normalized`.
