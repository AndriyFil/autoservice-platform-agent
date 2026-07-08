# AutoService Lessons

Reusable corrections and project-specific operating lessons for AutoService agents.

Agents must check this file before non-trivial tasks and apply any relevant lesson. When the user gives a correction that will be useful again, append it here in the format below.

## Lesson Format

```md
## YYYY-MM-DD - Short Lesson Title

- Correction: What the user corrected.
- Lesson: What agents should do next time.
- Applies when: The kinds of tasks where this matters.
```

## 2026-06-25 - Normal Safe Work Should Not Require Special Mode Names

- Correction: The user should not need to type special execution mode names for normal safe agent work.
- Lesson: Agents may inspect and edit files needed for the requested task by default, while still asking before broad, destructive, dependency, service, migration, Docker, log, or external-state-changing commands.
- Applies when: Any agent is planning, implementing, verifying, or documenting ordinary scoped work.

## 2026-06-25 - Chat-First Intake Extraction

- Correction: Do not build fake-smart regex intake parsing.
- Lesson: Chat-first intake extraction is either AI-backed or a conservative manual fallback. Manual fallback may safely preserve original text and extract narrow, low-risk fields like phone, but must not pretend to understand vehicle, diagnosis, repair, price, or availability.
- Applies when: Implementing or reviewing customer intake extraction, fallback parsing, LLM boundaries, or public intake flows.

## 2026-06-25 - Reusable Corrections Become Lessons

- Correction: User corrections should become project lessons when they are reusable.
- Lesson: When a correction would prevent future mistakes across tasks, append a concise lesson to this file and mention it in `.ai/task-report.md`.
- Applies when: The user corrects workflow, architecture, scope, verification, reporting, product, or design behavior.

## 2026-06-26 - Public Landing Is Chat-First Only

- Correction: The public landing page must not show workshop cards or link to the old `/book/{slug}` workshop-specific flow.
- Lesson: Keep the public landing page focused only on chat-first intake: header, hero, intake textarea, example chips, submit state, and received state.
- Applies when: Implementing or reviewing the public landing page, chat-first intake entry point, or legacy workshop booking exposure.

## 2026-06-27 - Phone Is Primary Intake Identity

- Correction: Chat-first intake must collect phone because the workshop needs it to contact the customer.
- Lesson: Missing-field detection for public intake requires only phone. Vehicle and preferred time may be extracted as optional enrichment, but they must not be treated as required next fields.
- Applies when: Implementing or reviewing intake extraction, missing-field detection, fallback parsing, LLM prompts, or customer follow-up flows.

## 2026-06-28 - Prefer Strategies For Expandable Branching

- Correction: Avoid growing many `if` branches when the behavior is likely to expand soon; use a strategy-style boundary with an interface or abstract class when it reduces future change risk.
- Lesson: Small conditionals are fine for stable local checks, but expandable business selection rules should move behind focused strategy objects instead of accumulating branch logic.
- Applies when: Implementing or reviewing backend rules with ordered choices, replaceable algorithms, provider-specific behavior, or near-future expansion paths.

## 2026-06-28 - Avoid Over-Safe Tiny Mappers

- Correction: Do not add excessive helper methods for simple array mapping safety.
- Lesson: For small DTO mappers, prefer direct readable field mapping and only keep guards that protect a real edge case or business rule.
- Applies when: Mapping decoded JSON, request-shaped arrays, DTO payloads, or other small boundary data.

## 2026-06-28 - Centralize Intake Field Values In Enums

- Correction: Missing intake field values should not be hardcoded in rules, queries, or tests.
- Lesson: Put reusable intake field values and labels in an enum and reference that enum from rules, read models, and tests to avoid drift.
- Applies when: Implementing or reviewing missing-field detection, intake extraction, LLM schema mapping, dashboard/admin queue read models, or tests around intake fields.

## 2026-06-29 - Public Intake Is Tenant Scoped

- Correction: Public intake must not create unassigned `BookingRequest` records or rely on central assignment; each workshop has its own `/w/{workshop:slug}` public intake page.
- Lesson: Public intake must receive the route `Workshop`, set `booking_requests.workshop_id` from it, and never use default/first workshop fallback, workshop cards, central admin queues, or claim workflows for routing.
- Applies when: Implementing or reviewing public intake routes, `SubmitIntakeRequestAction`, dashboard request visibility, product docs, or tests around SaaS workshop scoping.

## 2026-07-01 - Public Intake Starts As New

- Correction: Public intake is always workshop-scoped, so `submitted` is redundant with `new`.
- Lesson: Use `BookingRequestStatus::New` as the initial public intake status and do not reintroduce unassigned intake queue semantics.
- Applies when: Implementing or reviewing booking request statuses, public intake creation, dashboard status labels, product docs, migrations, or tests around initial intake state.

## 2026-07-03 - Separate Estimate Snapshot From PDF Rendering

- Correction: Do not put estimate snapshot lifecycle and PDF generation/storage in the same class.
- Lesson: Keep estimate snapshot versioning rules in one focused action, then pass the prepared `Estimate` object into a separate PDF generation action that only renders/stores the document.
- Applies when: Implementing or reviewing estimate creation, estimate versioning, document history, PDF generation, or action responsibility boundaries.

## 2026-07-03 - Estimate Generation Creates Versions

- Correction: Staff may generate estimate PDFs repeatedly; repeated generation must create a new estimate version, not rebuild the latest generated estimate.
- Lesson: Treat each allowed estimate generation as a new immutable `Estimate` snapshot with the next version and its own PDF document. Do not archive, overwrite, or mutate previous generated estimate versions just because staff generated again.
- Applies when: Implementing or reviewing estimate PDF generation, estimate versioning, document history, dashboard estimate lists, or tests around repeated estimate generation.

## 2026-07-03 - Public Intake Requires Only Message And Phone

- Correction: Public intake MVP requires only the customer's message and phone; vehicle, customer name, and preferred time are optional enrichment for staff to confirm later.
- Lesson: Do not force customers to add vehicle or preferred time after submit, do not make vehicle/name required for chat-first intake, and do not auto-create `Vehicle` records from AI/manual extraction. Preserve `original_message` and explicit `customer_phone`; staff should confirm or create vehicle details later in the dashboard.
- Applies when: Implementing or reviewing public intake validation, success messages, extraction missing-field logic, `BookingRequest` creation, or dashboard conversion flows.

## 2026-07-03 - Customer Is Not Platform User

- Correction: Workshop clients are `Customer` records, while `User` records are platform login accounts for owners, staff, and admins.
- Lesson: Public intake and booking-request conversion must not create login `User` records for customers. When opening a repair order from a booking request, resolve or create `Customer` by active `workshop_id` plus phone, keep customer name optional, and set `RepairOrder.created_by_user_id` to the current staff `User`.
- Applies when: Implementing or reviewing customer intake, booking request conversion, customer/vehicle selection, repair order creation, authentication, or customer-cabinet ideas.

## 2026-07-04 - RepairOrder Estimated Is Not Closed

- Correction: `RepairOrderStatus::Estimated` means an estimate PDF was generated and is waiting for approval; it is not completed or closed.
- Lesson: Superseded by the 2026-07-05 MVP lifecycle lesson below. Keep `estimated` distinct from `completed`; do not treat an estimate PDF as closed work.
- Applies when: Implementing or reviewing repair-order status actions, estimate generation/regeneration, dashboard mutation buttons, or tests around repair-order lifecycle.

## 2026-07-05 - RepairOrder Approval Removed For MVP

- Correction: Repair orders do not use an `approved` status in the MVP lifecycle.
- Lesson: Keep repair-order transitions centralized in `RepairOrderStatus::canTransitionTo()`: draft can become estimated, in_progress, or cancelled; estimated can become in_progress or cancelled; in_progress can become completed or cancelled; completed and cancelled are terminal. Estimate PDF generation is allowed while a repair order is draft, estimated, or in_progress; generating an estimate for an in_progress order must not regress its status to estimated. Do not add approval actions, buttons, or labels until a customer portal, signed estimate, or invoice approval flow explicitly requires it.
- Applies when: Implementing or reviewing repair-order status actions, estimate generation/regeneration, dashboard mutation buttons, translations, or lifecycle tests.

## 2026-07-05 - Estimates Can Change During Work

- Correction: Staff can still change/generate estimates while a repair order is in progress; only completed or cancelled orders are locked.
- Lesson: Allow estimate generation/regeneration for `draft`, `estimated`, and `in_progress` repair orders. Preserve `in_progress` status after estimate generation instead of moving the order back to `estimated`.
- Applies when: Implementing or reviewing estimate generation, repair-order status action availability, lifecycle tests, or completed/cancelled locking rules.

## 2026-07-06 - Prefer Stateful Domain Helpers For Phone Values

- Correction: Do not model phone normalization as a stateless `PhoneNormalizer` service when a small `Phone` object can hold the raw value and expose normalization behavior.
- Lesson: For phone-specific behavior, use a focused `Phone` Pure Fabrication/value-style helper with the raw phone passed through the constructor, then call behavior such as `normalize()`.
- Applies when: Implementing or reviewing phone normalization, customer matching, booking request intake phone storage, or phone search behavior.

## 2026-07-07 - Estimate Approval Requirement Is Per Repair Order

- Correction: Estimate approval requirement is not a workshop-level setting.
- Lesson: Store `requires_estimate_approval` on each `RepairOrder`, default it to true for new repair orders, allow staff to disable it per order when appropriate, and snapshot that value to `Estimate.requires_customer_approval` during estimate generation.
- Applies when: Implementing or reviewing estimate approval settings, repair-order creation, booking-request conversion, estimate generation, or future customer approval links.

## 2026-07-07 - Status Badge Is The Status Control

- Correction: Jira-like status changes should happen by clicking the status badge itself, not by adding a separate selectbox or adjacent change-status button.
- Lesson: When a dashboard status is editable, make the badge/pill the dropdown trigger in both detail headers and tables. Keep valid transitions backend-provided and do not duplicate transition rules in Vue.
- Applies when: Implementing or reviewing editable status UI for repair orders or similar dashboard workflow records.
