# Task Report

## Goal

Remove `estimated` as a RepairOrder operational status and keep estimate generation in the Estimate domain without adding approval flow, customer links, notifications, or new statuses.

## Files Changed

- `.ai/lessons/autoservice.md`
- `app/Actions/Estimates/PrepareEstimateForPdfAction.php`
- `app/Domain/RepairOrders/Actions/ChangeRepairOrderStatusAction.php`
- `app/Domain/RepairOrders/Enums/RepairOrderStatus.php`
- `app/Domain/RepairOrders/Queries/RepairOrderShowQuery.php`
- `app/Domain/RepairOrders/Services/RepairOrderStatusTransitionService.php`
- `app/Http/Controllers/DashboardRepairOrderController.php`
- `app/Http/Controllers/EstimateDashboardRepairOrderController.php`
- `app/Http/Requests/GenerateRepairOrderEstimateRequest.php`
- Removed `app/Http/Requests/MarkRepairOrderEstimatedRequest.php`
- `database/migrations/2026_07_10_000001_remove_estimated_repair_order_status.php`
- `docs/product/domain-model.md`
- `docs/product/mvp-scope.md`
- `lang/en/repair_orders.php`
- `lang/pl/repair_orders.php`
- `lang/uk/repair_orders.php`
- `resources/js/components/customers/types.ts`
- `resources/js/components/dashboard/types.ts`
- `resources/js/components/repair-orders/RepairOrderEstimatesTab.vue`
- `resources/js/components/repair-orders/RepairOrderStatusActions.vue`
- `resources/js/components/repair-orders/RepairOrderStatusBadge.vue`
- `resources/js/components/repair-orders/types.ts`
- `resources/js/pages/Dashboard/BookingRequests/Show.vue`
- `tests/Feature/EstimateDocumentManagementTest.php`
- `tests/Feature/GenerateRepairOrderEstimateActionTest.php`
- `tests/Feature/PrepareEstimateForPdfActionTest.php`
- `tests/Feature/RepairOrderLineManagementTest.php`
- `tests/Feature/RepairOrderManagementTest.php`
- `tests/Unit/RepairOrderStatusTest.php`

## Implementation Summary

`RepairOrderStatus` now contains only `draft`, `in_progress`, `completed`, and `cancelled`.

Manual transitions are:

- `draft -> in_progress, cancelled`
- `in_progress -> draft, completed, cancelled`
- `completed -> []`
- `cancelled -> []`

Estimate PDF generation no longer changes a draft repair order to `estimated`. It remains allowed for draft and in-progress orders with lines, and remains blocked for completed or cancelled orders.

The dashboard estimate action prop was renamed from `canMarkEstimated` to `canGenerateEstimate`, and frontend status unions plus badge colors no longer include `estimated`.

A migration converts existing `repair_orders.status = estimated` rows to `draft`. PostgreSQL/MySQL get a check constraint for the remaining allowed values. SQLite is skipped because this project uses in-memory SQLite for tests and SQLite cannot safely add this table constraint after table creation.

## Architecture Decisions

RepairOrderStatus remains the single source of status-transition rules through `manualTransitions()` and `canTransitionTo()`.

Estimate generation stays in the existing estimate actions; it creates estimate snapshots but does not own RepairOrder lifecycle state.

The estimate endpoint request class was renamed to `GenerateRepairOrderEstimateRequest` so HTTP naming matches the actual use case instead of the removed status.

Transition labels moved into `repair_orders.status_actions` translations instead of staying hardcoded in the transition service.

## Tradeoffs

The Inertia prop rename is a small frontend contract change, but it removes stale RepairOrder status terminology and reduces future confusion.

The migration maps existing `estimated` repair orders to `draft` for development/portfolio cleanup, as requested. It does not infer whether work already started because that would require business data the current schema does not reliably encode.

Historical lessons in `.ai/lessons/autoservice.md` were not rewritten; a newer superseding lesson was appended so future agents apply the current decision while preserving history.

## Tests

Commands run:

```sh
php artisan test tests/Feature/RepairOrderManagementTest.php
php artisan test tests/Feature/BookingRequestManagementTest.php
php artisan test
composer analyse
npm run build
```

Results:

- `php artisan test tests/Feature/RepairOrderManagementTest.php`: passed, 51 tests / 498 assertions.
- `php artisan test tests/Feature/BookingRequestManagementTest.php`: not found; this exact file does not exist.
- `php artisan test`: passed, 264 tests / 2017 assertions.
- `composer analyse`: failed in sandbox because PHPStan could not bind `tcp://127.0.0.1:0`; rerun with escalation was blocked by the environment approval/usage guard.
- `npm run build`: passed. Vite reported stale Browserslist/caniuse-lite data, but build completed.

## Risks

`composer analyse` did not complete because of environment restrictions, so static-analysis verification remains open.

SQLite test databases do not receive the added check constraint, but application validation and enum casting reject `estimated`, and PostgreSQL/MySQL migrations add the database constraint.

Remaining `estimated` text references are intentional: invalid-value tests, the cleanup migration, the unrelated `estimated_price` public-intake schema assertion, and historical lessons that are superseded by the new lesson.

## Follow Ups

- Run `composer analyse` outside the restricted sandbox.
- Consider a future `docs/learning/repair-order-status-vs-estimate-status.md` note to explain why operational order state and estimate lifecycle state are separate.
