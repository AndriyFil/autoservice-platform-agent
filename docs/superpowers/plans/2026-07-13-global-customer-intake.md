# Global Customer Intake Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace all workshop-specific public intake pages with one four-step intake at `/` that creates a workshop-scoped `BookingRequest` only after final submission.

**Architecture:** The repurposed `PublicIntakeController` reads workshop choices through one public query and renders `Welcome`. A global POST validates the selected workshop identifier, resolves the workshop on the server through the same availability query, and passes the model plus customer-entered snapshots into `SubmitPublicIntakeAction`. The Vue page owns one form object across four steps and posts only from review; public intake never creates `Customer`, `User`, `Vehicle`, or `RepairOrder` records and does not invoke intake extraction.

**Tech Stack:** Laravel, Eloquent, Inertia, Vue 3, TypeScript, Vitest, PHPUnit, PostgreSQL.

## Global Constraints

- `/` is the only public customer intake entry.
- Workshop selection stays in the same four-step form and never redirects by itself.
- A selected, server-validated workshop is required before submission; no unassigned `BookingRequest` is allowed.
- Create `BookingRequest` only on the final POST.
- Preserve existing phone normalization and initial `BookingRequestStatus::New` behavior.
- Store optional vehicle details as request-owned snapshots; never create `Customer`, `User`, `Vehicle`, or `RepairOrder` during public intake.
- Do not invoke AI extraction, diagnose, recommend repairs, estimate price, or promise availability.
- Remove both `/w/{workshop:slug}` and legacy `/book/{workshop:slug}` public intake flows and obsolete workshop-link props/UI/tests.
- Preserve unrelated uncommitted work and do not redesign the admin dashboard.

---

### Task 1: Global intake backend and behavior tests

**Files:**
- Create: `app/Domain/Workshops/Queries/AvailablePublicWorkshopsQuery.php`
- Create: `database/migrations/2026_07_13_000002_add_vehicle_snapshots_to_booking_requests_table.php`
- Remove: `app/Http/Controllers/PublicHomeController.php`
- Modify: `app/Http/Controllers/PublicIntakeController.php`
- Modify: `app/Http/Requests/StorePublicIntakeRequest.php`
- Modify: `app/Domain/BookingRequests/Actions/SubmitPublicIntakeAction.php`
- Modify: `app/Models/BookingRequest.php`
- Modify: `app/Domain/BookingRequests/Queries/BookingRequestIndexQuery.php`
- Modify: `app/Domain/BookingRequests/Queries/BookingRequestShowQuery.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/PublicIntakeSubmissionTest.php`
- Modify: `tests/Feature/PublicAdminDomainRoutingTest.php`
- Modify: `tests/Feature/RepairOrderTest.php`
- Modify: `tests/Feature/AdminWorkflowTest.php`
- Remove: `app/Http/Controllers/PublicBookingRequestController.php`
- Remove: `app/Http/Requests/StorePublicBookingRequestRequest.php`
- Remove: `app/Domain/BookingRequests/Actions/CreatePublicBookingRequestAction.php`
- Remove: `resources/js/pages/PublicBookingRequests/Create.vue`
- Remove: `resources/js/pages/PublicBookingRequests/Success.vue`
- Remove: `tests/Feature/PublicBookingRequestFlowTest.php`

**Interfaces:**
- `AvailablePublicWorkshopsQuery::options(): array<int, array{id:int,name:string}>`
- `AvailablePublicWorkshopsQuery::resolve(int $workshopId): Workshop`
- `StorePublicIntakeRequest::workshopId(): int`
- `StorePublicIntakeRequest::vehicle(): array{brand:?string,model:?string,year:?int,license_plate:?string}`
- `SubmitPublicIntakeAction::handle(Workshop $workshop, string $message, string $phone, array $vehicle): BookingRequest`

- [ ] Replace workshop-route tests with failing tests proving `/` exposes workshop options, `workshop_id` is required, invalid identifiers fail, the selected workshop owns the created request, no record exists before final POST, no unassigned request exists, and no `Customer`, `User`, `Vehicle`, or `RepairOrder` is created.
- [ ] Run `php artisan test tests/Feature/PublicIntakeSubmissionTest.php tests/Feature/PublicAdminDomainRoutingTest.php tests/Feature/RepairOrderTest.php` and confirm failures are caused by the missing global flow.
- [ ] Add optional `vehicle_brand`, `vehicle_model`, `vehicle_year`, and `vehicle_license_plate` snapshot columns and model fillable entries; use them as the dashboard read-model fallback when no linked Vehicle exists.
- [ ] Implement the public workshop query with deterministic name/id ordering and no invented availability rule beyond existing workshops.
- [ ] Make `/` render workshop options and submitted state; add throttled `POST /intake`; remove `/w/{slug}` and `/book/{slug}` routes.
- [ ] Validate problem, phone, selected workshop, optional vehicle snapshots, and honeypot in the FormRequest; resolve the workshop through the public query before invoking the Action.
- [ ] Make the Action create exactly one workshop-scoped request with normalized phone and snapshots and no extractor call.
- [ ] Remove legacy public booking implementation and update affected tests.
- [ ] Re-run the focused PHP tests and confirm they pass.

### Task 2: Same-page four-step Vue intake

**Files:**
- Create: `resources/js/components/public-intake/types.ts`
- Create: `resources/js/components/public-intake/PublicIntakeFlow.vue`
- Create: `resources/js/components/public-intake/PublicIntakeSuccess.vue`
- Modify: `resources/js/pages/Welcome.vue`
- Modify: `resources/js/pages/Welcome.test.ts`
- Remove: `resources/js/pages/PublicIntake.vue`

**Interfaces:**
- `WorkshopOption = { id: number; name: string }`
- `Welcome` receives `workshops`, `intakeSubmitted`, and existing admin URL/auth props.
- The final form payload contains `message`, `phone`, `vehicle.brand`, `vehicle.model`, `vehicle.year`, `vehicle.license_plate`, `workshop_id`, and `website`.

- [ ] Update the Vitest source-level tests first to require the header actions, four named steps, one final global POST, workshop radios, review state, exact success copy, and `My requests` link; run them and confirm expected failure.
- [ ] Keep `Welcome.vue` as page orchestration and move wizard behavior into `PublicIntakeFlow.vue`.
- [ ] Use one `useForm` instance and numeric current step so Back/Edit/Continue preserve all entered data; only the review step calls `form.post(route('public-intake.store'))`.
- [ ] Implement step-local guidance, accessible labels/errors, native workshop radio inputs, an empty-workshop state, review/edit controls, and processing state.
- [ ] Replace the wizard after the redirect-backed submitted prop with the exact success message and a `My requests` link.
- [ ] Preserve the current warm cream/teal/slate visual language and mobile-first single-column layout.
- [ ] Run the focused Vitest file and `npm run build`.

### Task 3: Remove obsolete workshop-link props and document the decision

**Files:**
- Modify: `app/Domain/Workshops/Queries/WorkshopSettingsQuery.php`
- Modify: `resources/js/pages/Dashboard/Workshop/type.ts`
- Modify: `resources/js/pages/Dashboard/Workshop/Settings.vue`
- Modify: `tests/Feature/WorkshopAdminTest.php`
- Modify: `tests/Feature/PublicAdminDomainRoutingTest.php`
- Remove: `resources/js/components/workshop/PublicIntakeLink.vue`
- Remove: `resources/js/components/workshop/PublicIntakeLink.test.ts`
- Modify: `docs/architecture/autoservice-ddd-rules.md`
- Modify: `.ai/lessons/autoservice.md`
- Modify: `.ai/task-report.md`

**Interfaces:**
- Workshop settings no longer exposes `publicIntakePath` or `publicIntakeUrl`.
- Architecture rules state that `/` is global intake, selection precedes submission, every request is workshop-scoped, and `/w/{slug}` was removed.

- [ ] Remove public workshop-link props, component usage, component test, and obsolete assertions without changing other workshop settings behavior.
- [ ] Update architecture documentation with the four explicit routing/scoping decisions.
- [ ] Add a concise lesson superseding the prior workshop-specific landing/intake lessons.
- [ ] Inspect the complete diff using the AutoService reviewer checklist, including cross-workshop isolation, action/controller boundaries, dead route references, frontend type duplication, and unrelated changes.
- [ ] Run focused public-intake tests, `php artisan test`, focused Vitest, and `npm run build`; record exact results and environment failures in `.ai/task-report.md`.
