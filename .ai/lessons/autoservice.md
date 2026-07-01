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

- Correction: Chat-first intake must ask for phone before vehicle when both are missing, because the workshop needs phone to contact the customer.
- Lesson: Missing-field priority for intake extraction is phone, then vehicle, then preferred time, then null when enough information is present.
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

- Correction: Missing intake field values such as phone, vehicle, and preferred time should not be hardcoded in rules, queries, or tests.
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
