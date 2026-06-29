# Task Report

## Goal

Implement the backend/domain foundation for Repair Orders and staff-authored estimate lines.

## Files Changed

- `database/migrations/2026_06_29_000001_update_repair_orders_for_estimate_foundation.php`
- `database/migrations/2026_06_29_000002_create_repair_order_lines_table.php`
- `app/Enums/RepairOrderStatus.php`
- `app/Enums/RepairOrderLineType.php`
- `app/Models/RepairOrder.php`
- `app/Models/RepairOrderLine.php`
- `app/Models/Workshop.php`
- `app/Actions/RepairOrders/CreateRepairOrderAction.php`
- `app/Http/Requests/StoreRepairOrderRequest.php`
- `app/Queries/Dashboard/DashboardRepairOrderDetailsQuery.php`
- `app/Queries/Dashboard/DashboardRepairOrdersQuery.php`
- `database/factories/RepairOrderFactory.php`
- `database/factories/RepairOrderLineFactory.php`
- `tests/Unit/RepairOrderTotalsTest.php`
- `tests/Feature/RepairOrderTest.php`
- `tests/Feature/PublicIntakeSubmissionTest.php`
- `tests/Feature/RepairOrderManagementTest.php`
- `docs/product/domain-model.md`
- `docs/product/mvp-scope.md`
- `.ai/task-report.md`

Pre-existing unrelated dirty files were left in place:

- `app/Queries/Admin/UnassignedIntakeRequestsQuery.php`
- `tests/Feature/UnassignedIntakeRequestsQueryTest.php`
- `auto-service-landing-page-design.zip`
- `customer-communication-interface.zip`

## Implementation Summary

Added a `repair_order_lines` foundation for staff-authored estimate lines with supported line types: labor, part, fee, and discount.

Updated repair orders to support the milestone fields:

- nullable `customer_id`
- nullable `vehicle_id`
- nullable `problem_description`
- `notes`
- nullable `created_by_user_id`
- status values: `draft`, `estimated`, `approved`, `in_progress`, `completed`, `cancelled`

Existing `open` repair orders are migrated to `draft`.

Added deterministic model-level total calculations:

- `RepairOrderLine::subtotalCents()`
- `RepairOrderLine::taxCents()`
- `RepairOrderLine::totalCents()`
- `RepairOrder::subtotalCents()`
- `RepairOrder::taxCents()`
- `RepairOrder::totalCents()`

Public intake still creates only `BookingRequest` records and does not create repair orders.

## Architecture Decisions

Kept the foundation on `RepairOrder` and `repair_order_lines` instead of introducing a separate `Estimate` aggregate. The milestone asked for repair order lines directly, and a separate estimate workflow would add lifecycle complexity before there is a UI or approval process that needs it.

Money is stored as integer cents in `unit_price_cents`. Totals are calculated with integer arithmetic and explicit rounding, not floats.

`RepairOrderStatus::Draft` replaces the old `open` status as the initial staff-owned state. Existing complete/cancel actions remain thin use-case actions and continue to scope by active `WorkshopUser`.

The dashboard repair-order detail query now includes estimate totals but does not add full CRUD UI or customer approval behavior.

## Tradeoffs

Tax support is intentionally small: each line has a simple `tax_rate`, and totals are deterministic. This is not a tax engine and does not handle accounting rules.

Discounts are represented as line items that reduce totals. This keeps the line model simple but means discounts share the same quantity/unit-price shape as other lines.

Repair order lines are model-level foundation only. There are no controllers, routes, or Inertia components for editing lines yet.

The status enum now includes future workflow states, but this milestone only keeps the existing create/complete/cancel behavior active.

## Tests

Run:

```txt
php artisan test
```

Result:

```txt
121 passed, 950 assertions
```

Added or updated coverage for:

- repair order workshop relationship
- optional booking request link
- repair order has many lines
- cent-based line and repair order totals
- discount lines reducing totals
- draft repair order existing without invoice
- public intake not creating repair orders automatically
- existing repair order management behavior using `draft`

## Risks

Existing frontend copy or TypeScript types may still use the word `open` if not covered by backend feature tests. No full frontend build was requested or run.

SQLite in-memory tests passed. If production uses another database, the nullable column alteration migration should be checked during deployment planning.

## Follow Ups

- Add staff UI and Actions/FormRequests for creating and editing repair order lines.
- Decide when a repair order should move from `draft` to `estimated`.
- Add authorization rules for estimate-line editing once routes exist.
- Consider a future `docs/learning/money-cents-and-rounding.md` note if the team wants a practical explanation of cent-based money handling.
- Later milestone: invoice generation from approved or completed repair orders, after approval/payment/PDF scope is explicitly defined.
