# Task Report

## Goal

Continue the practical DDD-lite modular monolith migration for RepairOrders by finishing the remaining read-query placement under `app/Domain/RepairOrders` without changing existing UI behavior, routes, lifecycle rules, or workshop isolation.

## Files Changed

- `app/Domain/RepairOrders/Queries/RepairOrderFormQuery.php`
- `app/Http/Controllers/DashboardRepairOrderController.php`
- Removed `app/Queries/Dashboard/DashboardRepairOrderFormQuery.php`

## Implementation Summary

Moved the repair-order create-form read model from the generic dashboard query namespace into `App\Domain\RepairOrders\Queries`.

Renamed `DashboardRepairOrderFormQuery` to `RepairOrderFormQuery` and updated `DashboardRepairOrderController` to use the domain query.

Preserved the existing form payload shape, customer matching behavior, booking-request prefill behavior, active-workshop scoping, duplicate repair-order redirect behavior, and `requires_estimate_approval` default.

## Architecture Decisions

Repair-order reads now live together in `app/Domain/RepairOrders/Queries`: `RepairOrderFormQuery`, `RepairOrderIndexQuery`, and `RepairOrderShowQuery`.

Controllers remain HTTP orchestration only: the dashboard repair-order controller resolves active workshop context, calls domain queries/actions, and returns Inertia or redirects.

No Eloquent models were moved. No repositories, mappers, data classes, or broad services were added because they would not reduce complexity for this slice.

## Tradeoffs

`CreateRepairOrderFromBookingRequestAction` stays in `app/Domain/BookingRequests/Actions` because it is the booking-request conversion use case, while it imports RepairOrders enums and creates a `RepairOrder` with active-workshop constraints.

Data classes remain deferred because the existing validated arrays are still small and readable.

The totals calculator remains deferred because totals are already centralized and tested on `RepairOrderLine` and `RepairOrder`.

Only the existing focused exception `FinalRepairOrderCannotBeChanged` is present; additional domain exceptions were deferred because current `DomainException` handling already integrates with form errors.

## Tests

Commands run:

```sh
php artisan test tests/Feature/RepairOrderManagementTest.php
php artisan test tests/Feature/DashboardBookingRequestManagementTest.php
php artisan test tests/Feature/CustomerManagementTest.php
php artisan test tests/Feature/PublicIntakeSubmissionTest.php
php artisan test tests/Feature/RepairOrderLineManagementTest.php
php artisan test tests/Unit/RepairOrderStatusTest.php tests/Unit/RepairOrderTotalsTest.php
php artisan test
composer analyse
npm run build
```

Results:

- `RepairOrderManagementTest`: 52 passed, 521 assertions.
- `DashboardBookingRequestManagementTest`: 18 passed, 239 assertions.
- `CustomerManagementTest`: 18 passed, 170 assertions.
- `PublicIntakeSubmissionTest`: 16 passed, 146 assertions.
- `RepairOrderLineManagementTest`: 10 passed, 126 assertions.
- `RepairOrderStatusTest` + `RepairOrderTotalsTest`: 14 passed, 47 assertions.
- Full `php artisan test`: 265 passed, 2047 assertions.
- `composer analyse`: passed with no errors after rerunning with escalation because sandboxed PHPStan could not bind its local TCP worker socket.
- `npm run build`: passed. Vite reported stale Browserslist/caniuse-lite data, but the build completed.

Not run:

- `php artisan test tests/Feature/BookingRequestManagementTest.php` because that file does not exist. The project uses `tests/Feature/DashboardBookingRequestManagementTest.php`, which passed.

## Risks

Risk is low: this was a namespace/class move for an already-tested read query, and the focused plus full backend test suite passed.

Any external code importing `App\Queries\Dashboard\DashboardRepairOrderFormQuery` would need to use `App\Domain\RepairOrders\Queries\RepairOrderFormQuery`, but no in-repo references remain.

## Follow Ups

- Next recommended domain to migrate: `Estimates`, because estimate generation is the next adjacent workflow and already depends on RepairOrders lifecycle rules.
- Consider a future `docs/learning/laravel-domain-modules.md` note if future agents need a practical guide to this repo's DDD-lite module boundaries.
