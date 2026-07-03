# Task Report

## Goal

Refactor the RepairOrder show page into tabs so overview, working lines, estimates, documents, and timeline are no longer mixed on one cluttered screen.

## Files Changed

- `resources/js/pages/Dashboard/RepairOrders/Show.vue` - converted the page body into a five-tab layout and kept top-level repair-order actions in the header.
- `resources/js/components/repair-orders/RepairOrderOverviewTab.vue` - added the summary-only overview tab with status, customer, vehicle, problem/source, working total, and latest estimate summary.
- `resources/js/components/repair-orders/RepairOrderLinesTab.vue` - added the Lines tab wrapper for working lines and working totals.
- `resources/js/components/repair-orders/RepairOrderEstimatesTab.vue` - added the Estimates tab wrapper with the allowed generate-estimate action and estimate versions list.
- `resources/js/components/repair-orders/RepairOrderDocumentsTab.vue` - added the Documents tab with filename, type, status, generated date, download, and empty state.
- `resources/js/components/repair-orders/RepairOrderTimelineTab.vue` - moved the existing timeline/source request details into a dedicated tab.
- `resources/js/components/repair-orders/RepairOrderLinesSection.vue` - changed visible price input from cents to decimal money, improved responsive form/table sizing, and tightened edit/delete button layout.
- `resources/js/components/repair-orders/utils.ts` and `utils.test.ts` - added decimal-money-to-cents conversion helpers and focused Vitest coverage.
- `resources/js/components/repair-orders/types.ts` - added the repair-order document DTO used by the Documents tab.
- `app/Queries/Dashboard/DashboardRepairOrderDetailsQuery.php` - added a scoped `documents` prop from repair-order estimate documents for the Documents tab.
- `tests/Feature/RepairOrderManagementTest.php` - added coverage that the show page exposes document metadata and download URLs.
- `lang/en/repair_orders.php`, `lang/pl/repair_orders.php`, `lang/uk/repair_orders.php` - added tab/document/overview labels and replaced the visible unit cents label with unit price.
- `.ai/task-report.md` - updated with this task report.

Note: the working tree already contained unrelated modified files before this task, especially estimate-generation actions/tests and `.ai/lessons/autoservice.md`. Those were left intact.

## Implementation Summary

The show page now uses tabs:

- Overview: repair order status, customer phone/name, vehicle, problem, source/original request when available, current working total, latest estimate summary.
- Lines: add-line form, working repair order lines, edit/delete controls, and working totals.
- Estimates: estimate versions, status, total, generated date, PDF download, and generate estimate button when allowed.
- Documents: document list with filename/type/status/generated date/download, or an empty state.
- Timeline: the existing opened/closed/source request/original message/preferred date information.

The line price field now displays and accepts decimal money such as `123.45`. The frontend converts that value back to `unit_price_cents` only when submitting to the existing Laravel request.

## Architecture Decisions

- Kept the controller and write/action flow unchanged.
- Added only a read-model prop for documents because the UI did not have enough data for the requested Documents tab.
- Kept feature-specific tab components in `resources/js/components/repair-orders/` instead of creating global tab or document abstractions.
- Reused the existing estimate generation/status action behavior and did not change estimate regeneration rules.

## Tradeoffs

- The tab control is local page markup rather than a shared UI primitive because no reusable tabs component exists yet and this need is repair-order-specific.
- Internal names still include cents where they represent backend storage/request fields; visible UI labels no longer expose cents wording.
- Documents are currently sourced from estimate documents because those are the documents available in the existing model relationships.

## Tests

- `npm run build` - passed; Vite reported only the existing Browserslist data age warning.
- `php artisan test tests/Feature/RepairOrderManagementTest.php` - passed, 25 tests / 232 assertions.
- `php artisan test tests/Feature/GenerateEstimatePdfActionTest.php` - passed, 2 tests / 7 assertions.
- `php artisan test` - passed, 172 tests / 1349 assertions.
- `composer analyse` - first sandboxed run failed with `EPERM` while PHPStan tried to bind `127.0.0.1:0`; escalated rerun passed with no errors.
- `npm run test -- resources/js/components/repair-orders/utils.test.ts` - passed, 1 file / 13 tests.
- `git diff --check` - passed.

## Risks

- Existing unrelated dirty files remain in the worktree and may appear in broader diffs.
- The backend request field remains `unit_price_cents`; this is intentional storage/API shape, but future UI work should continue hiding cents wording from users.

## Follow Ups

- Consider renaming `canMarkEstimated` to estimate-generation wording in a focused cleanup if that prop is touched again.
- Consider a local visual smoke test of the tabs in-browser once a dev server is intentionally started.
