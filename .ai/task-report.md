# Task Report

## Goal

Continue the practical DDD-lite modular monolith migration by moving RepairOrder business logic into `app/Domain/RepairOrders` while preserving existing routes, UI behavior, workshop isolation, and repair-order lifecycle rules.

## Files Changed

- Created `app/Domain/RepairOrders/Actions/`
- Created `app/Domain/RepairOrders/Enums/`
- Created `app/Domain/RepairOrders/Exceptions/`
- Created `app/Domain/RepairOrders/Queries/`
- Created `app/Domain/RepairOrders/Services/`
- Moved repair-order actions from `app/Actions/RepairOrders/` into `app/Domain/RepairOrders/Actions/`
- Moved `RepairOrderStatus` and `RepairOrderLineType` from `app/Enums/` into `app/Domain/RepairOrders/Enums/`
- Moved dashboard repair-order index/show queries from `app/Queries/Dashboard/` into `app/Domain/RepairOrders/Queries/`
- Updated repair-order controllers, requests, models, factories, estimate code, booking-request conversion code, and tests to import the new domain namespaces.
- Updated `tests/Feature/RepairOrderLineManagementTest.php`
- Updated `tests/Unit/RepairOrderStatusTest.php`

## Implementation Summary

Moved normal repair-order creation, status changes, estimate-approval requirement updates, and line add/update/remove actions into `App\Domain\RepairOrders\Actions`.

Moved `RepairOrderIndexQuery` and `RepairOrderShowQuery` into `App\Domain\RepairOrders\Queries`. They continue to scope reads by the active workshop membership and preserve existing payload shape for Inertia pages.

Moved `RepairOrderStatus` and `RepairOrderLineType` into `App\Domain\RepairOrders\Enums`. `RepairOrderStatus` still has only `draft`, `estimated`, `in_progress`, `completed`, and `cancelled`, and now exposes `isFinal()` for completed/cancelled checks.

Added `RepairOrderStatusTransitionService` for backend-provided manual dropdown transitions and labels. It intentionally keeps the existing estimate-generation flow: `estimated` remains a valid enum transition, but the manual status dropdown still does not offer direct `draft -> estimated`.

Added `FinalRepairOrderCannotBeChanged` and used it to block repair-order line mutations and estimate approval requirement changes after completed/cancelled.

After review, line and approval-requirement mutations now re-read the active-workshop repair order with `lockForUpdate()` inside the same transaction as the mutation. This keeps final-status locks safe against concurrent status changes.

## Architecture Decisions

Eloquent models stayed in `app/Models`; only business actions, read queries, enums, one focused service, and one focused exception moved into the RepairOrders domain.

Controllers remain HTTP orchestration: they resolve active workshop context, call domain actions/queries, catch domain exceptions, and return redirects/Inertia responses.

The enum remains the source of truth for allowed lifecycle transitions. The transition service only maps manual UI actions and labels, preventing duplicated dropdown-label logic in index/show queries.

Data classes were deferred because current validated arrays remain small and local. A totals calculator was deferred because totals behavior is already centralized on `RepairOrderLine`/`RepairOrder` methods and not duplicated across actions.

## Tradeoffs

`DashboardRepairOrderFormQuery` remains in `app/Queries/Dashboard` because the requested migration named index/show queries, and the form query is mostly page form preparation with BookingRequest customer matching. Moving it later may make sense when dashboard form-read models are migrated as a group.

`VehicleDoesNotBelongToCustomer` and `InvalidRepairOrderStatusTransition` were not added because current `DomainException` messages already integrate cleanly with existing form error handling. Only final-status mutation received a named exception because it is now reused by multiple actions.

Repair-order line totals remain model methods rather than a service to avoid moving clean, tested arithmetic into an abstraction without reducing duplication.

## Tests

Red/green check for new final line lock:

```sh
php artisan test tests/Feature/RepairOrderLineManagementTest.php --filter=final_repair_order_lines_cannot_be_added_updated_or_deleted
```

First run failed because final orders still allowed mutation. After implementation, it passed.

Commands run:

```sh
php artisan test tests/Feature/RepairOrderLineManagementTest.php
php artisan test tests/Feature/RepairOrderManagementTest.php
php artisan test tests/Feature/DashboardBookingRequestManagementTest.php
php artisan test tests/Feature/CustomerManagementTest.php
php artisan test tests/Unit/RepairOrderStatusTest.php
php artisan test
composer analyse
```

Results:

- `RepairOrderLineManagementTest`: 10 passed, 126 assertions.
- `RepairOrderManagementTest`: 52 passed, 521 assertions.
- `DashboardBookingRequestManagementTest`: 18 passed, 239 assertions.
- `CustomerManagementTest`: 18 passed, 170 assertions.
- `RepairOrderStatusTest`: 6 passed, 27 assertions.
- Full `php artisan test`: 265 passed, 2047 assertions.
- `composer analyse`: passed with no errors after rerunning with escalation because sandboxed PHPStan could not bind its local TCP worker socket.
- `git diff --check`: passed.

Review follow-up fixed:

- Wrapped line add/update/remove and approval-requirement updates in transactions with locked repair-order reads.
- Added cancelled repair-order coverage for estimate approval requirement lock.

Not run:

- `php artisan test tests/Feature/BookingRequestManagementTest.php` because that file does not exist; `DashboardBookingRequestManagementTest.php` was run instead.
- `npm run build` because no frontend files changed.

## Risks

This migration touches many imports because PHP enum namespaces changed. Full tests and PHPStan passed, which reduces the risk of stale imports.

No old RepairOrder enum/action/query wrapper classes were left behind. Any external code importing the old namespaces would need to move to the new domain namespaces.

## Follow Ups

- Next recommended domain to migrate: `Estimates`, because estimate generation already imports RepairOrder status and is the next adjacent business workflow.
- Consider `docs/learning/laravel-domain-modules.md` if future agents need a practical note on this repo's DDD-lite module boundaries.
