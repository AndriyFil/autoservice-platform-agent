# Task Report

## Goal

Refactor estimate generation so the dashboard controller calls one high-level repair order estimate use-case action instead of orchestrating lower-level preparation and PDF generation actions.

## Files Changed

- `app/Actions/Estimates/GenerateRepairOrderEstimateAction.php` - new high-level use-case action that prepares the estimate snapshot, renders/stores the PDF through the existing lower-level action, and returns controller-facing result metadata.
- `app/Actions/Estimates/GenerateRepairOrderEstimateResult.php` - new small result object with the generated `Document` and whether the request was a regeneration.
- `app/Http/Controllers/EstimateDashboardRepairOrderController.php` - now injects and calls only `GenerateRepairOrderEstimateAction`, catches domain errors, redirects, and chooses the flash message from the action result.
- `tests/Feature/GenerateRepairOrderEstimateActionTest.php` - covers create and regenerate behavior through the new high-level action.
- `tests/Feature/EstimateDocumentManagementTest.php` - adds coverage that the estimate route delegates to the single high-level action.
- `.ai/task-report.md` - updated with this task outcome.

Note: the working tree already contained many unrelated modified/untracked files before this task. This report lists the scoped files changed for this request.

## Implementation Summary

Old controller flow:

`EstimateDashboardRepairOrderController::store()` checked whether a generated estimate existed, called `PrepareEstimateForPdfAction`, then called `GenerateEstimatePdfAction`.

New controller flow:

`EstimateDashboardRepairOrderController::store()` calls `GenerateRepairOrderEstimateAction::handle($activeWorkshopUser, $repairOrder)` once. The controller no longer queries estimate state or invokes PDF-generation internals.

The high-level action determines whether this is a regeneration, delegates estimate snapshot lifecycle to `PrepareEstimateForPdfAction`, delegates PDF rendering/storage/document creation to `GenerateEstimatePdfAction`, and returns a `GenerateRepairOrderEstimateResult`.

## Architecture Decisions

- Kept the existing active workshop pattern by passing `WorkshopUser` into the action; `PrepareEstimateForPdfAction` still reloads and scopes the repair order by active workshop membership.
- Kept `PrepareEstimateForPdfAction` as the owner of estimate creation/regeneration, line rebuilding, total recalculation, locked-state checks, and generated-document archiving.
- Kept `GenerateEstimatePdfAction` as the PDF-only boundary so rendering/storage stays separate from estimate snapshot lifecycle.
- Added a tiny result object because the controller needs regeneration state for the flash message without inspecting domain state itself.
- Did not rename `MarkRepairOrderEstimatedRequest`; it is now a little stale, but renaming it would broaden the change across request names/tests without improving the controller boundary.

## Tradeoffs

- The high-level action still accepts `WorkshopUser` instead of plain `actor: User` because current dashboard authorization and action scoping are based on resolved active workshop membership.
- Regeneration detection is computed before preparation by checking for a current generated estimate. That matches existing product behavior and keeps flash text out of lower-level actions.
- PDF rendering/storage remains after snapshot preparation, preserving separation but retaining the existing retry caveat if storage/rendering fails after preparation.

## Tests

- `php artisan test tests/Feature/GenerateRepairOrderEstimateActionTest.php` - first run failed as expected because the new action did not exist.
- `php artisan test tests/Feature/GenerateRepairOrderEstimateActionTest.php tests/Feature/EstimateDocumentManagementTest.php` - passed, 10 tests / 78 assertions.
- `php artisan test tests/Feature/GenerateEstimatePdfActionTest.php` - passed, 2 tests / 7 assertions.
- `php artisan test tests/Feature/RepairOrderManagementTest.php` - passed, 24 tests / 210 assertions.
- `php artisan test` - passed, 171 tests / 1312 assertions.
- `composer analyse` - first sandboxed run failed with `EPERM` while PHPStan tried to listen on `127.0.0.1`; escalated rerun passed with no errors.
- `git diff --check` - passed with no whitespace errors.

## Risks

- Existing unrelated dirty worktree changes remain and may affect future diffs independently of this task.
- `MarkRepairOrderEstimatedRequest` naming is still product-language debt; leaving it avoids unnecessary churn in this refactor.

## Follow Ups

- Consider a focused rename from `MarkRepairOrderEstimatedRequest` to estimate-generation wording when touching the request layer next.
- Consider a learning note at `docs/learning/laravel-action-composition.md` explaining why this use-case action composes two lower-level actions instead of moving all logic into the controller or one large service.
