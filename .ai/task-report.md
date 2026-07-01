# Task Report

## Goal

Build the first usable staff repair-order estimate workflow: create repair orders from reviewed booking requests, manage repair order lines, see totals, and mark draft repair orders as estimated.

## Files Changed

- `app/Actions/RepairOrders/CreateRepairOrderAction.php`
- `app/Actions/RepairOrders/AddRepairOrderLineAction.php`
- `app/Actions/RepairOrders/UpdateRepairOrderLineAction.php`
- `app/Actions/RepairOrders/DeleteRepairOrderLineAction.php`
- `app/Actions/RepairOrders/MarkRepairOrderEstimatedAction.php`
- `app/Http/Controllers/DashboardRepairOrderLineController.php`
- `app/Http/Controllers/EstimateDashboardRepairOrderController.php`
- `app/Http/Requests/StoreRepairOrderLineRequest.php`
- `app/Http/Requests/UpdateRepairOrderLineRequest.php`
- `app/Http/Requests/MarkRepairOrderEstimatedRequest.php`
- `app/Queries/Dashboard/DashboardBookingRequestsQuery.php`
- `app/Queries/Dashboard/DashboardRepairOrderDetailsQuery.php`
- `routes/web.php`
- `resources/js/components/dashboard/BookingRequestTable.vue`
- `resources/js/components/dashboard/types.ts`
- `resources/js/components/repair-orders/RepairOrderLinesSection.vue`
- `resources/js/components/repair-orders/RepairOrderStatusActions.vue`
- `resources/js/components/repair-orders/RepairOrderTotalsSummary.vue`
- `resources/js/components/repair-orders/RepairOrderStatusBadge.vue`
- `resources/js/components/repair-orders/types.ts`
- `resources/js/components/repair-orders/utils.ts`
- `resources/js/pages/Dashboard/BookingRequests/Show.vue`
- `resources/js/pages/Dashboard/RepairOrders/Show.vue`
- `tests/Feature/DashboardBookingRequestManagementTest.php`
- `tests/Feature/RepairOrderManagementTest.php`
- `tests/Feature/RepairOrderLineManagementTest.php`
- `docs/product/domain-model.md`
- `docs/product/mvp-scope.md`
- `.ai/task-report.md`

Some files already had unrelated working-tree changes before this task; those changes were left intact.

## Implementation Summary

Added focused line-management Actions, FormRequests, controllers, and dashboard routes for adding, updating, and deleting repair order lines.

Added `MarkRepairOrderEstimatedAction` and a thin controller route for `draft -> estimated`. The action requires the repair order to belong to the active workshop, be in `draft`, and have at least one line.

Expanded dashboard repair-order detail props to include lines, per-line totals, repair-order totals, available line types, source booking request text, and backend-owned status action availability.

Updated the staff UI so repair order details show status actions, line add/edit/delete controls, and estimate totals. Dashboard booking requests now expose repair order link state and show a “Start work” action for confirmed requests without a repair order.

Tightened repair-order creation from a booking request so the persisted repair order copies the booking request problem description instead of trusting posted replacement text.

Updated product docs to record that the current estimate is represented by `RepairOrder + RepairOrderLines + estimated status`, not a separate `Estimate` entity.

## Architecture Decisions

Controllers remain thin HTTP adapters. They receive FormRequests, call one Action, and redirect with flash or validation errors.

Business rules live in Actions: workshop scoping, line ownership checks, duplicate-source protection through existing create flow, and estimate transition rules.

Totals stay on `RepairOrder` and `RepairOrderLine`, preserving the existing integer-cents domain behavior and avoiding duplicated math in Vue.

The frontend displays backend-provided `statusActions` instead of deciding estimate transition availability itself.

No Estimate model, invoice, payment, PDF, approval flow, AI pricing, or diagnosis UI was added.

## Tradeoffs

Line forms accept unit prices in cents to match the current database and avoid currency assumptions. A future currency/workshop-money display task can improve formatting once the product defines currency.

Complete/cancel actions were left in the existing repair-order workflow. This task only added the estimated transition and did not redesign the broader repair-order lifecycle.

The line editor is intentionally feature-local and plain. It can be split further if the table grows, but a larger abstraction is not needed yet.

## Tests

Run:

```txt
php artisan test tests/Feature/PublicIntakeSubmissionTest.php tests/Feature/DashboardTest.php
php artisan test tests/Feature/DashboardBookingRequestManagementTest.php tests/Feature/RepairOrderManagementTest.php
php artisan test tests/Feature/RepairOrderLineManagementTest.php tests/Unit/RepairOrderTotalsTest.php
php artisan test
npm run build
```

Results:

```txt
PublicIntakeSubmissionTest + DashboardTest: 19 passed, 265 assertions
DashboardBookingRequestManagementTest + RepairOrderManagementTest: 36 passed, 359 assertions
RepairOrderLineManagementTest + RepairOrderTotalsTest: 10 passed, 96 assertions
Full PHP suite: 130 passed, 1090 assertions
npm run build: failed to start because vite is not installed in node_modules
```

## Risks

Frontend build could not be verified in this workspace because `vite` was unavailable. The changed Vue files were statically searched for stale repair-order `open` status usage; only dialog/sidebar state usages of `open` remain.

Money display is currency-neutral for now. Staff enter integer cents, and the UI formats cents as decimal amounts without a currency symbol.

## Follow Ups

- Install frontend dependencies and run `npm run build` or the project’s TypeScript check before release.
- Consider `docs/learning/laravel-formrequest-action-query-workflow.md` to explain this Controller -> FormRequest -> Action -> Query pattern with examples from this milestone.
