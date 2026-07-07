# Task Report

## Goal

Implement proper Customer and Vehicle handling when staff creates a RepairOrder from a BookingRequest.

## Files Changed

- `app/Actions/RepairOrders/CreateRepairOrderFromBookingRequestAction.php` - preserves booking-request source text for the repair-order problem description, keeps customer lookup scoped to active workshop plus `customer_phone_normalized`, verifies selected vehicles against customer/workshop, and creates a new vehicle only when no existing vehicle is selected.
- `app/Queries/Dashboard/DashboardRepairOrderFormQuery.php` - preloads the booking-request source message/problem text for the create form and uses original message as the form fallback when `problem_description` is blank.
- `resources/js/pages/Dashboard/RepairOrders/Create.vue` - shows the original customer message on the booking-request conversion form.
- `resources/js/pages/Dashboard/RepairOrders/type.ts` - adds source booking-request text fields to the Inertia page type.
- `tests/Feature/RepairOrderManagementTest.php` - adds/updates tests for normalized customer creation, optional customer names, existing-customer name preservation, source text fallback, selected/new vehicle precedence, and source form props.

## Implementation Summary

- Customer lookup during BookingRequest conversion uses the active workshop id plus `booking_requests.customer_phone_normalized`.
- New Customer creation uses the booking request phone fields and allows `name` to stay null.
- Existing Customer reuse does not overwrite the stored customer name from submitted booking-request/customer-name input.
- RepairOrder creation stores `created_by_user_id` from the current staff `WorkshopUser`.
- RepairOrder `problem_description` comes from `BookingRequest.problem_description` when present, otherwise from `BookingRequest.original_message`.
- Existing selected vehicles are verified by both active workshop and resolved customer.
- New vehicle fields create a Vehicle only when `vehicle_id` is not selected; Vehicle remains optional.

## Architecture Decisions

- Kept the write flow in `Controller -> FormRequest -> Action -> Model/DB`; the controller still only selects the correct Action and handles redirects/errors.
- Kept customer/vehicle ownership checks in the Action because they are business invariants, not simple request shape validation.
- Kept the create-page read logic in the existing form Query because booking-request source mapping and preselection are read-model concerns.
- Did not add a shared service for vehicle resolution even though manual and booking-request Actions have similar code; this task only needed one behavior correction, and a broader extraction would need a focused follow-up to avoid speculative abstraction.

## Tradeoffs

- `customers.name` was already nullable, so no migration or placeholder name was added.
- The UI change is intentionally small and stays in the existing page because the form is not large enough to justify a new component for this slice.
- If both `vehicle_id` and `new_vehicle` are posted, selected existing vehicle wins. This matches the product rule that new vehicle is created only when no existing vehicle is selected.

## Tests

- `php artisan test tests/Feature/RepairOrderManagementTest.php` - passed, 42 tests / 474 assertions.
- `php artisan test tests/Feature/CustomerManagementTest.php` - passed, 7 tests / 106 assertions.
- `php artisan test tests/Feature/PublicIntakeSubmissionTest.php` - passed, 16 tests / 146 assertions.
- `php artisan test` - passed, 205 tests / 1661 assertions.
- `composer analyse` - first sandbox run failed with TCP listener `EPERM`; escalated rerun passed with no PHPStan errors.
- `npm run build` - passed. Vite reported the existing Browserslist data-age warning.

## Risks

- Manual repair-order creation still has similar vehicle-resolution code. It passed the suite, but future changes could drift unless a small shared helper/action is introduced deliberately.
- I did not run migrations because this task did not require schema changes and migrations are outside normal safe execution unless explicitly requested.

## Follow Ups

- Consider a focused `docs/learning/action-ownership-checks.md` note explaining why cross-workshop and vehicle/customer ownership checks belong in Actions instead of Vue or FormRequest validation.
- Consider a later cleanup to extract duplicated manual/booking-request vehicle resolution only if another task changes that rule again.
