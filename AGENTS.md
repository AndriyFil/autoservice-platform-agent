# AutoService AI Instructions

## Purpose

AutoService is a Laravel + Inertia + Vue modular monolith for workshop intake and repair-order management.

Prefer clear Laravel conventions and practical DDD-lite. Do not add architecture, abstractions, infrastructure, or tooling unless the current task needs them.

The main architecture reference is:

`docs/architecture/autoservice-ddd-rules.md`

Read it when a task changes business rules, domain boundaries, routes, persistence, or product flow. Do not reread unrelated documentation for small isolated edits.

## Default Execution Mode

Work as a single agent.

- Do not spawn subagents unless the user explicitly requests subagents.
- Do not automatically invoke architect, product, UI, backend, frontend, reviewer, or documentation agents.
- Apply the necessary architecture, UX, implementation, and review judgment in the primary thread.
- Perform one focused self-review before finishing.
- When subagents are explicitly requested, use at most two with small, non-overlapping responsibilities.
- Do not create agent pipelines or multiple review passes.

## Scope Discipline

Implement only the requested change.

Do not add:

- unrelated refactoring
- speculative abstractions
- future features
- additional architecture layers
- new infrastructure
- broad UI redesign
- compatibility with platforms the project does not support

When you find a possible improvement outside the task, mention it briefly in the final summary instead of implementing it.

Do not reinterpret a focused task as permission to redesign the surrounding feature.

## Token and Context Budget

Keep work proportional to the task.

- Inspect only relevant files first.
- Use targeted searches instead of broad repository-wide analysis when possible.
- Do not repeat the full project context in plans or reports.
- Do not create long planning documents for straightforward tasks.
- Use a short internal plan for non-trivial changes.
- Do not generate documentation, learning notes, or task reports unless the task materially changes product or architecture, or the user explicitly requests them.

## Validation Budget

Use focused validation by default.

- Run the smallest relevant test file or test filter.
- Do not run the full Laravel test suite after every task.
- Do not run a production frontend build for backend-only work.
- For frontend changes, prefer focused frontend tests and type/lint checks when available.
- Run the full test suite or production build only when:
  - the user explicitly requests it;
  - the task is a milestone or merge-preparation task;
  - a shared/core change creates meaningful regression risk.
- Do not run the same validation repeatedly unless a failure required another change.

Report commands actually run and their results. Do not claim tests passed if they were not run.

## Safety and External State

You may inspect and edit files needed for the requested task.

Ask before:

- dependency installation or updates
- Docker commands
- service startup or shutdown
- database migrations
- destructive database operations
- commands that change external services
- broad formatting commands with write mode
- log inspection containing potentially sensitive data

Safe scoped inspection includes commands such as:

- `rg`
- `sed`
- `git diff`
- `git status`
- `php artisan route:list`

Do not expose secrets, API keys, full phone numbers, or customer messages in logs or reports.

## Architecture

Use practical DDD-lite.

- Eloquent models stay in `app/Models`.
- HTTP controllers, requests, and middleware stay in `app/Http`.
- Business use cases and domain-specific read logic live in `app/Domain/{Context}`.
- Public and Admin are UI surfaces, not domain modules.
- Do not introduce generic repositories, persistence mappers, duplicate domain entities, CQRS infrastructure, event sourcing, or microservices unless explicitly requested and justified by a real problem.

Patterns must pay rent: they must reduce complexity, duplication, unclear responsibility, change risk, or testing difficulty.

### Backend responsibility split

Default write flow:

`Controller -> FormRequest -> Action -> Model/DB`

Controller:

- HTTP orchestration
- resolve authenticated/active context
- call Action or Query
- return Inertia response, redirect, or download
- no multi-model business workflow
- no business transaction

FormRequest:

- validate and shape HTTP input
- request-level authorization where appropriate
- no persistence
- no business workflow

Action:

- one business use case
- business rules and coordinated writes
- transaction ownership when multiple records form one operation
- workshop-scoped access enforcement where required

Query/read class:

- non-trivial filtering, eager loading, pagination, aggregation, or presentation mapping
- keep large Eloquent chains and nested payload mapping out of controllers
- do not create a Query class for a trivial read that remains clear in the controller

Model:

- relationships
- casts
- persistence configuration
- small model behavior
- no large multi-record workflow

Policy:

- use when authorization rules are reusable or more complex than route/auth guards
- do not create policies mechanically

### Frontend responsibility split

Page components orchestrate page layout and server props.

- Extract large feature sections, lists, forms, cards, and dialogs into feature components.
- Keep page-local state local.
- Do not introduce a store for page-local Inertia props.
- Use shared stores only for genuinely shared application state.
- Keep reusable TypeScript types in nearby feature type files.
- Avoid god components and duplicate frontend types.
- Keep user-visible labels in translation files or the existing frontend localization layer, not in domain services.

## Database

AutoService is PostgreSQL-only.

- Do not add MySQL, SQLite, or generic cross-database branches unless explicitly requested.
- Do not use `DB::getDriverName()` to create fake multi-database support.
- Prefer explicit PostgreSQL migrations and constraints.
- Do not edit old migrations merely to make code look cleaner unless the user specifically requests it.
- Do not run migrations without permission.

## Workshop Isolation

Workshop-scoped operations must derive access through active `WorkshopUser` membership.

- Do not use a direct `user.workshop_id` shortcut.
- Do not trust workshop, customer, vehicle, booking request, repair order, estimate, or document identifiers from the request without server-side scoping.
- Prevent cross-workshop reads and writes.
- Prefer the project's established 404/403 behavior consistently.

## Stable Business Rules

Unless the architecture document says otherwise:

- `Customer` is not `User`.
- Customer-facing access must not create staff/login users.
- Customers remain workshop-scoped records.
- A `Vehicle` belongs to a `Customer`.
- A public intake creates a workshop-scoped `BookingRequest`, not a `RepairOrder`.
- A `RepairOrder` is an internal workshop order.
- Do not create unassigned booking requests.
- RepairOrder operational statuses are:
  - `draft`
  - `in_progress`
  - `completed`
  - `cancelled`
- Do not add `estimated` or `approved` to `RepairOrderStatus`.
- Estimate/approval state belongs to the Estimates domain.
- AI may assist intake extraction later, but must not diagnose, price, promise availability, or replace staff confirmation.

When current code and architecture documentation disagree, stop and report the mismatch before making a product-direction decision.

## Testing Expectations

For a behavior change, add or update focused tests for:

- the changed happy path
- the most important failure path
- workshop isolation when relevant
- duplicate prevention when relevant

Do not add broad end-to-end or race-condition tests unless they protect the requested behavior or the user asks for them.

Use fakes for external providers. Tests must not call real paid APIs or send real messages.

## Documentation and Reports

Update `docs/architecture/autoservice-ddd-rules.md` only when an accepted product or architecture rule changed.

Update `.ai/task-report.md` only when:

- the user explicitly asks;
- the task is a major multi-file feature;
- the task changes architecture or product direction;
- the existing workflow explicitly requires a milestone report.

Do not spawn a documentation agent solely to update a report.

For normal tasks, the final response should contain only:

- concise summary
- files changed
- validation run and result
- one relevant unresolved issue, if any

Do not print full file contents unless requested.

## Completion Rule

A task is complete when:

1. the requested behavior is implemented;
2. unrelated behavior was not changed;
3. focused validation is complete or clearly reported as not run;
4. one self-review found no blocking issue;
5. the agent stops.

Do not continue into the next feature automatically.
