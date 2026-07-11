# Task Report

## Goal

Clean up the DDD migration by removing the thin repair-order status transition service and moving existing document code into `app/Domain/Documents` without changing behavior, migrations, or UI.

## Files Changed

- `app/Domain/Documents/Enums/DocumentStatus.php`
- `app/Domain/Documents/Enums/DocumentType.php`
- `app/Domain/Documents/Services/WorkshopDocumentStorage.php`
- `app/Domain/RepairOrders/Queries/RepairOrderIndexQuery.php`
- `app/Domain/RepairOrders/Queries/RepairOrderShowQuery.php`
- `app/Domain/RepairOrders/Services/RepairOrderStatusTransitionService.php`
- `app/Enums/DocumentStatus.php`
- `app/Enums/DocumentType.php`
- `app/Support/Documents/WorkshopDocumentStorage.php`
- `app/Domain/Estimates/Actions/GenerateEstimatePdfAction.php`
- `app/Models/Document.php`
- `app/Models/Estimate.php`
- `database/factories/DocumentFactory.php`
- `tests/Feature/EstimateDocumentManagementTest.php`
- `tests/Feature/GenerateEstimatePdfActionTest.php`
- `tests/Feature/GenerateRepairOrderEstimateActionTest.php`
- `tests/Feature/PrepareEstimateForPdfActionTest.php`
- `tests/Feature/RepairOrderManagementTest.php`
- `.ai/task-report.md`

## Implementation Summary

Moved the existing document status enum, document type enum, and workshop document storage class into the new `app/Domain/Documents` context.

Updated application code, models, factory, and feature tests to import the new document namespaces.

Removed `RepairOrderStatusTransitionService` and now build `availableStatusTransitions` directly in the repair-order index and show query classes from `RepairOrderStatus::manualTransitions()`.

## Architecture Decisions

Kept Eloquent models in `app/Models` and HTTP code untouched, matching `docs/architecture/autoservice-ddd-rules.md`.

Kept document label behavior unchanged inside the moved enums because this task was a namespace/domain ownership cleanup, not a label refactor.

Kept repair-order transition rules only on `RepairOrderStatus`; the query/application layer now translates transition labels for props.

## Tradeoffs

The available status transition mapping now appears in two query classes. That keeps the removed service from coming back as a fake domain service, but a future repeated read-model helper could be considered if more repair-order query props need the same mapping.

No new document actions, notifications, approval links, database branches, migrations, or UI changes were added.

## Tests

- Failed: `php artisan test tests/Feature/GenerateEstimatePdfActionTest.php`
  - 2 failed, 0 assertions.
  - Blocked during migration setup by SQLite failing on `ALTER TABLE repair_orders ADD CONSTRAINT ...` in `database/migrations/2026_07_10_000001_remove_estimated_repair_order_status.php`.
- Failed: `php artisan test tests/Feature/RepairOrderManagementTest.php`
  - 51 failed, 0 assertions.
  - Same SQLite migration setup failure.
- Failed: `php artisan test`
  - 39 passed, 225 failed, 141 assertions.
  - Same SQLite migration setup failure across database-backed tests.
- Passed: `composer analyse`
  - PHPStan checked 120 files with no errors.

## Risks

The requested Laravel tests cannot complete in the current default SQLite test setup while the PostgreSQL-style repair-order check-constraint migration is present. That migration was explicitly left untouched.

External or pending-branch code importing the old document namespaces will need matching import updates.

## Follow Ups

- Consider aligning the test environment with the documented PostgreSQL-only project direction so feature tests do not run migrations against SQLite.
