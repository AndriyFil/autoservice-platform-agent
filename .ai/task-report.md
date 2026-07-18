# Task Report

## Goal

Replace the marketing homepage and workshop-specific public intake pages with one mobile-first, four-step customer intake at `/` that creates a `BookingRequest` for a server-validated workshop only on final submission.

## Files Changed

Created:

- `app/Domain/Workshops/Queries/AvailablePublicWorkshopsQuery.php` — lists public workshop options and resolves the selected workshop through one server-side boundary.
- `database/migrations/2026_07_13_000002_add_vehicle_snapshots_to_booking_requests_table.php` — adds request-owned vehicle snapshots and enforces non-null `workshop_id` after guarding against legacy unassigned rows.
- `resources/js/components/public-intake/PublicIntakeFlow.vue` — implements the four-step same-page intake and final submission.
- `resources/js/components/public-intake/PublicIntakeSuccess.vue` — shows the submitted state and `My requests` link.
- `resources/js/components/public-intake/state.ts` — keeps step navigation, validation routing, focus targets, and review-only submission testable outside the Vue component.
- `resources/js/components/public-intake/state.test.ts` — covers state preservation, step navigation, accessibility focus/announcements, and final-step-only submission.
- `resources/js/components/public-intake/types.ts` — centralizes workshop option and form payload types.
- `docs/superpowers/plans/2026-07-13-global-customer-intake.md` — records the approved implementation plan.

Modified:

- `app/Http/Controllers/PublicIntakeController.php` — renders `Welcome` with workshop options, resolves the selected workshop, invokes the intake Action, and redirects back to `/` with the submitted state.
- `app/Http/Requests/StorePublicIntakeRequest.php` — validates message, phone, workshop identifier, optional vehicle snapshots, and honeypot input; exposes shaped values to the controller.
- `app/Domain/BookingRequests/Actions/SubmitPublicIntakeAction.php` — creates one workshop-scoped request with normalized phone and optional vehicle snapshots, without AI extraction or related-domain creation.
- `app/Domain/BookingRequests/Queries/BookingRequestIndexQuery.php` and `BookingRequestShowQuery.php` — expose request-owned vehicle snapshots when no linked `Vehicle` exists.
- `app/Domain/Workshops/Queries/WorkshopSettingsQuery.php` — removes obsolete workshop-specific public intake URL props.
- `app/Models/BookingRequest.php` — allows and casts the vehicle snapshot fields.
- `bootstrap/app.php` — preserves the original customer message whitespace for the new global `POST /intake` endpoint.
- `routes/web.php` — makes `/` the global intake page, adds `POST /intake`, and removes workshop-specific and legacy booking routes.
- `resources/js/pages/Welcome.vue` — replaces the marketing hero with the global intake flow while keeping `My requests`, `Staff login`, and `Create workshop account` in the header.
- `resources/js/pages/Welcome.test.ts` — covers the global intake page, header actions, four steps, workshop selection, final submission, success state, and accessibility hooks.
- `resources/js/pages/Dashboard/Workshop/Settings.vue` and `type.ts` — remove the obsolete public intake link and its props without changing other settings behavior.
- `tests/Feature/PublicIntakeSubmissionTest.php` — covers the homepage options, required/invalid workshop handling, selected-workshop ownership, final-submit-only creation, non-null workshop scope, no related records, snapshots, success state, removed routes, honeypot, and throttling.
- `tests/Feature/PublicAdminDomainRoutingTest.php`, `WorkshopAdminTest.php`, `AdminWorkflowTest.php`, and `RepairOrderTest.php` — update route/prop expectations and affected workflows for global intake.
- `tests/Feature/DashboardTest.php` and `DashboardBookingRequestManagementTest.php` — cover vehicle snapshot presentation without a linked `Vehicle`.
- `docs/architecture/autoservice-ddd-rules.md` — documents `/` as the global intake entry, same-page workshop selection before submission, mandatory workshop scope, and removal of `/w/{slug}` intake.
- `.ai/lessons/autoservice.md` — adds the reusable global same-page intake lesson that supersedes the 2026-06-26 and 2026-06-29 directions.
- `.ai/task-report.md` — records implementation, architecture, tradeoffs, verification evidence, and remaining risk.

Removed:

- `app/Http/Controllers/PublicHomeController.php`
- `app/Http/Controllers/PublicBookingRequestController.php`
- `app/Http/Requests/StorePublicBookingRequestRequest.php`
- `app/Domain/BookingRequests/Actions/CreatePublicBookingRequestAction.php`
- `resources/js/pages/PublicIntake.vue`
- `resources/js/pages/PublicBookingRequests/Create.vue`
- `resources/js/pages/PublicBookingRequests/Success.vue`
- `resources/js/components/workshop/PublicIntakeLink.vue`
- `resources/js/components/workshop/PublicIntakeLink.test.ts`
- `tests/Feature/PublicBookingRequestFlowTest.php`

## Implementation Summary

- `/` now immediately presents a four-step flow: Problem; Contact and vehicle; Workshop; Review and submit.
- One frontend form object preserves customer input across Continue, Back, and Edit navigation. Workshop choice stays on the same page and does not submit or redirect.
- Only the review step sends `POST /intake`. The backend validates the workshop identifier, resolves the `Workshop` through `AvailablePublicWorkshopsQuery`, and passes the model into `SubmitPublicIntakeAction`.
- The Action preserves existing phone normalization and `BookingRequestStatus::New`, stores optional vehicle details as `BookingRequest` snapshots, and creates no `Customer`, `User`, `Vehicle`, or `RepairOrder`.
- Successful submission returns to `/` and replaces the form with a clear confirmation and a `My requests` link.
- Removed public routes: `GET /w/{workshop:slug}`, `POST /w/{workshop:slug}/intake`, `GET /book/{workshop:slug}`, `POST /book/{workshop:slug}`, and `GET /book/{workshop:slug}/success`.
- Workshop settings no longer exposes or renders workshop-specific intake URLs.

## Architecture Decisions

- Workshop availability and resolution share one Query boundary so the option list and server-side validation use the same workshop source. The client-provided identifier is never treated as a trusted `Workshop`.
- The controller remains HTTP orchestration only; validation and request shaping stay in the FormRequest, while creation and phone normalization stay in the single-use-case Action.
- `BookingRequest.workshop_id` is enforced as non-null at the database level. The migration stops with an explicit error if legacy unassigned rows exist rather than assigning them speculatively.
- Optional vehicle details remain immutable request context until staff confirmation. They are snapshots on `BookingRequest`, not premature `Customer` or `Vehicle` records.
- The Vue page orchestrates props and layout; feature components and feature-local types/state own the multi-step behavior. No page-local store or AI boundary was added.

## Tradeoffs

- All existing workshops are currently selectable because the project has no established public-availability flag. The Query provides the focused place to add a real availability rule later.
- Vehicle details are denormalized snapshots by design. This duplicates a small amount of vehicle-shaped data but preserves the public request without creating an unconfirmed domain record.
- The success state is redirect-backed, so a browser refresh after the flashed session value is consumed returns to a fresh intake form.
- Source-level Vue coverage is complemented by pure state tests; no browser end-to-end test was added in this slice.

## Tests

Recorded TDD and review evidence:

- Backend focused RED: `13` failures, `12` passes.
- Backend review RED after additional assertions: `3` failures, `13` passes.
- Frontend RED: `6` failures, `1` pass.
- Frontend accessibility review RED: `8` failures, `13` passes.

Final fresh verification:

- `php artisan test tests/Feature/PublicIntakeSubmissionTest.php` — `17` tests passed, `123` assertions.
- `php artisan test` — `290` tests passed, `2395` assertions.
- Focused frontend suites — `13` of `13` tests passed across `2` files.
- `npm run build` — production build completed successfully with `2078` modules transformed.
  - Non-blocking warning: installed `caniuse-lite` browser data is stale.
- The initial sandboxed PHP run could not connect to PostgreSQL; the final focused and full suites passed after PostgreSQL access was available.

## Risks

- Several secondary documents still describe the former workshop-specific or legacy booking flow: `.ai/context/autoservice-chat-first-context.md`, `docs/architecture/adr-001-mvp-domain-database-model.md`, `docs/architecture/public-vs-admin-surface.md`, `docs/product/mvp-scope.md`, `docs/product/admin-workshop-inbox.md`, `docs/product/domain-model.md`, and `docs/product/business-rules.md`. They were intentionally left unchanged because this task requested only `docs/architecture/autoservice-ddd-rules.md`; they should be reconciled in a separately approved documentation task. The older 2026-06-26 and 2026-06-29 entries in `.ai/lessons/autoservice.md` remain as history but are explicitly superseded by the 2026-07-13 lesson.
- The migration intentionally fails if an environment contains legacy unassigned `BookingRequest` rows. Such data must be reviewed and assigned explicitly before migration.
- Existing unrelated worktree changes were preserved and were not staged or committed.

## Follow Ups

- If workshops later need to opt out of public intake, define the business rule and apply it inside `AvailablePublicWorkshopsQuery` rather than filtering independently in the controller or frontend.
- Reconcile the stale context, architecture, and product documents listed under Risks with the global same-page intake flow in a separately approved documentation cleanup.
