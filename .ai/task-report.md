# Task Report

## Goal

Continue the practical DDD-lite modular monolith migration by moving BookingRequest status, public intake, dashboard read models, customer matching, and repair-order conversion logic into `app/Domain/BookingRequests`.

## Files Changed

- `app/Domain/BookingRequests/Enums/BookingRequestStatus.php`
- `app/Domain/BookingRequests/Actions/SubmitPublicIntakeAction.php`
- `app/Domain/BookingRequests/Actions/CreatePublicBookingRequestAction.php`
- `app/Domain/BookingRequests/Actions/ChangeBookingRequestStatusAction.php`
- `app/Domain/BookingRequests/Actions/CreateRepairOrderFromBookingRequestAction.php`
- `app/Domain/BookingRequests/Queries/BookingRequestIndexQuery.php`
- `app/Domain/BookingRequests/Queries/BookingRequestShowQuery.php`
- `app/Domain/BookingRequests/Services/CustomerMatcher.php`
- `app/Domain/BookingRequests/Data/.gitkeep`
- `app/Domain/BookingRequests/Exceptions/.gitkeep`
- `app/Http/Controllers/DashboardBookingRequestController.php`
- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/DashboardRepairOrderController.php`
- `app/Http/Controllers/PublicBookingRequestController.php`
- `app/Http/Controllers/PublicIntakeController.php`
- `app/Http/Requests/UpdateDashboardBookingRequestStatusRequest.php`
- `app/Models/BookingRequest.php`
- `app/Queries/Dashboard/DashboardRepairOrderFormQuery.php`
- `database/factories/BookingRequestFactory.php`
- `database/seeders/DatabaseSeeder.php`
- `resources/js/pages/Dashboard/BookingRequests/Show.vue`
- `tests/Feature/CustomerManagementTest.php`
- `tests/Feature/DashboardBookingRequestManagementTest.php`
- `tests/Feature/DashboardTest.php`
- `tests/Feature/PublicBookingRequestFlowTest.php`
- `tests/Feature/PublicIntakeSubmissionTest.php`
- `tests/Feature/RepairOrderManagementTest.php`
- `tests/Feature/RepairOrderTest.php`
- Deleted old booking-request paths under `app/Actions/BookingRequests`, `app/Actions/RepairOrders/CreateRepairOrderFromBookingRequestAction.php`, `app/Enums/BookingRequestStatus.php`, and dashboard booking-request query paths under `app/Queries/Dashboard`.

## Implementation Summary

Moved `BookingRequestStatus` into the BookingRequests domain and updated models, requests, seeders, factories, controllers, and tests to import the new enum namespace.

Moved public intake and booking-request status actions into `Domain\BookingRequests\Actions`. The chat-first `SubmitPublicIntakeAction` continues to create a workshop-scoped `BookingRequest`, stores the original message and normalized phone, and lets optional extraction fail without blocking creation.

Moved dashboard booking-request list/show read flows into `Domain\BookingRequests\Queries` as `BookingRequestIndexQuery` and `BookingRequestShowQuery`. The show query still scopes by active workshop, includes original/extracted data, linked repair order, status transitions, and create-repair-order availability.

Moved `CreateRepairOrderFromBookingRequestAction` into the BookingRequests domain because the use case starts from a triaged booking request. It still enforces active-workshop scope, confirmed status, duplicate conversion prevention, customer resolution by workshop plus normalized phone, optional vehicle selection/creation, and default per-order estimate approval.

Added `CustomerMatcher` to centralize active-workshop customer matching by normalized booking-request phone. `BookingRequestShowQuery` and `DashboardRepairOrderFormQuery` now share that rule.

Updated legacy `/book/{workshop}` public booking submission behavior so it preserves the route but creates only a `BookingRequest`; it no longer creates or links `Customer` or `Vehicle` records during public submission.

Kept the create-order surface intentionally simple: one `canCreateRepairOrder` prop controls whether staff sees the Create order action. For `new` requests, that action auto-confirms internally before opening the repair-order form.

## Architecture Decisions

Controllers remain HTTP orchestration only. They resolve route models/active workshop context, call domain actions or queries, and return redirects/Inertia responses.

Eloquent models stayed in `app/Models`. The BookingRequests domain coordinates use cases around existing Laravel models without adding repositories or mappers.

`CustomerMatcher` is a focused service because phone-based customer matching was duplicated in multiple read flows and must always stay workshop-scoped. Broader customer creation behavior stays in the conversion action where the business use case needs it.

Data classes were deferred because the current validated arrays are small and local to one action each. Domain exceptions were deferred because existing `DomainException` handling already maps cleanly to current dashboard form errors.

## Tradeoffs

The legacy public booking route still accepts vehicle fields for compatibility with the existing form request, but those fields no longer create vehicles. Staff should confirm or create vehicle details during triage/repair-order creation.

For staff UX, `new -> confirmed -> create repair order form` remains one user action. No separate confirm-then-create prop is used because there is no separate user-visible action.

`CreateRepairOrderFromBookingRequestAction` still lives near booking-request conversion even though it writes a repair order. This keeps the source business use case and duplicate-conversion invariant together while leaving normal repair-order creation in the RepairOrders actions.

Empty `Data` and `Exceptions` folders were kept with `.gitkeep` to match the existing domain folder convention and leave room for future classes only when they pay rent.

## Tests

Passed syntax checks:

```sh
php -l app/Domain/BookingRequests/Actions/SubmitPublicIntakeAction.php
php -l app/Domain/BookingRequests/Actions/CreatePublicBookingRequestAction.php
php -l app/Domain/BookingRequests/Actions/ChangeBookingRequestStatusAction.php
php -l app/Domain/BookingRequests/Actions/CreateRepairOrderFromBookingRequestAction.php
php -l app/Domain/BookingRequests/Queries/BookingRequestIndexQuery.php
php -l app/Domain/BookingRequests/Queries/BookingRequestShowQuery.php
php -l app/Domain/BookingRequests/Services/CustomerMatcher.php
php -l app/Queries/Dashboard/DashboardRepairOrderFormQuery.php
php -l app/Http/Controllers/DashboardBookingRequestController.php
php -l app/Http/Controllers/DashboardController.php
php -l app/Http/Controllers/DashboardRepairOrderController.php
php -l app/Models/BookingRequest.php
php -l tests/Feature/PublicBookingRequestFlowTest.php
php -l tests/Feature/DashboardBookingRequestManagementTest.php
php -l tests/Feature/RepairOrderManagementTest.php
```

Not run due project workflow unless explicitly requested:

```sh
php artisan test tests/Feature/PublicIntakeSubmissionTest.php tests/Feature/PublicBookingRequestFlowTest.php tests/Feature/DashboardBookingRequestManagementTest.php tests/Feature/RepairOrderManagementTest.php tests/Feature/CustomerManagementTest.php tests/Feature/DashboardTest.php tests/Feature/RepairOrderTest.php
```

## Risks

The full feature suite was not executed in this turn. Public legacy booking tests were updated to the new hard rule, but the Vue page still exposes vehicle fields; that is compatible at the HTTP boundary but may be confusing until the legacy form is retired or simplified.

The booking-request migration is committed as `8c2cf96 move booking request to domain`, while this task report update remains uncommitted.

## Follow Ups

- Run the focused feature suites above before merging.
- Consider simplifying or retiring the legacy `/book/{workshop}` form so it no longer asks for vehicle details that public submission intentionally does not persist.
- Consider `docs/learning/laravel-domain-modules.md` if future agents need a practical learning note for DDD-lite Laravel module boundaries.
