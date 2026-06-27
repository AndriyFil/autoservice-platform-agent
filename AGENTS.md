# AutoService Platform AI Instructions

## Project approach

This is a Laravel AutoService / booking platform.

Use Laravel conventions first.
Use GRASP and SOLID as thinking tools, not as dogma.
Do not introduce architecture patterns unless they solve a visible problem.

## Main rule

Patterns must pay rent.

A pattern is acceptable only if it reduces:
- complexity
- duplication
- unclear responsibility
- risk of change
- testing difficulty

Do not produce "quick MVP now, clean later" code.
The default target is simple but correct architecture from the beginning.

## Workflow Rules

Use the workflow docs in `.ai/workflow/` for normal agent work:

- `.ai/workflow/README.md`
- `.ai/workflow/planning.md`
- `.ai/workflow/execution.md`
- `.ai/workflow/verification.md`
- `.ai/workflow/lessons.md`

For non-trivial tasks, agents must check `.ai/lessons/autoservice.md` before planning.

For non-trivial tasks, agents must plan first, implement the smallest useful slice, verify the result, update `.ai/task-report.md`, and stop.

When the user gives a reusable correction, append a concise lesson to `.ai/lessons/autoservice.md` before completing the task report.

Normal safe work should not require the user to type special mode names. Agents may inspect and edit files needed for the requested task by default. Agents must still ask before broad or destructive commands, dependency installation or updates, service startup, migrations, Docker operations, log inspection, or any command that changes external state beyond the requested work.

## Agent roles

Use roles intentionally:

- `autoservice-architect`: plans architecture and responsibility boundaries. Does not implement unless explicitly asked.
- `autoservice-backend-lead`: implements Laravel/backend work with senior-level maintainability standards.
- `autoservice-frontend-lead`: implements Vue/Inertia frontend work with senior-level component/state discipline.
- `autoservice-product-ux`: validates product flows, user journeys, and chat-first customer intake before implementation.
- `autoservice-ui-designer`: designs visual structure, component breakdowns, states, accessibility, and responsive behavior before frontend implementation.
- `autoservice-reviewer`: reviews diffs before acceptance.
- `autoservice-doc-writer`: documents existing decisions and creates learning notes when requested.

For large features, prefer:

```txt
autoservice-architect -> autoservice-backend-lead + autoservice-frontend-lead -> autoservice-reviewer -> autoservice-doc-writer when needed
```

For small backend-only tasks:

```txt
autoservice-backend-lead -> autoservice-reviewer
```

For small frontend-only tasks:

```txt
autoservice-frontend-lead -> autoservice-reviewer
```

## Product and Design Workflow

For new customer-facing flows, run `autoservice-product-ux` before `autoservice-frontend-lead`.

For visual implementation, run `autoservice-ui-designer` before `autoservice-frontend-lead`.

`autoservice-architect` still owns backend/domain boundaries, not visual UX.

## Chat-First Intake Direction

AutoService is a chat-first auto service intake and workshop management platform.

This is not an AI mechanic. This is an AI intake assistant.

LLM may be used only for:
- extracting structured intake data from customer text
- summarizing the customer's original message
- detecting missing required fields
- asking the next missing question

LLM must not:
- diagnose vehicle problems
- recommend repairs
- estimate prices
- promise appointment availability
- replace staff confirmation
- act as a general-purpose chatbot

Customer-facing intake must start from a natural customer message, for example:

```txt
Opel Insignia, check engine light came on, maybe sensors, when can I come?
```

The system should extract what it can, show a short confirmation summary, and ask only for missing required information. Do not force the customer to re-enter data already extracted.

Use examples, suggestions, or animated placeholder behavior to teach customers what to write. After submit, show:

```txt
Request received. A service advisor will contact you to confirm details and visit time.
```

Phone call remains the safest final confirmation channel. Customer-facing UI should look like a conversation or timeline, not a traditional form.

Product Manager must prevent AI diagnosis scope creep.
Product UX must prefer a free-text first message over rigid question-answer flow.
UI Designer must include first-message examples, placeholder or suggestion behavior, submitted-state message, and conversation/timeline layout.
Architect must keep future LLM integration behind a small intake extraction boundary, not spread AI calls across controllers or components.
Backend and Frontend leads must not implement LLM unless a spec explicitly asks for it.

## Required skills

For implementation workflow, follow:

- `.agents/skills/caveman-development-mode.md`
- `.agents/skills/autoservice-task-workflow.md`

For backend responsibility boundaries, follow:

- `.agents/skills/autoservice-controller-flow.md`
- `.agents/skills/autoservice-testing-strategy.md`

For frontend structure, follow:

- `.agents/skills/autoservice-frontend-structure.md`

For chat-first customer intake and product UX, follow:

- `.agents/skills/chat-first-ux-flow.md`

For review, follow:

- `.agents/skills/laravel-grasp-solid-review.md`

For learning notes, follow:

- `.agents/skills/autoservice-learning-note.md`

## Quality Update Rules

Architects must review these questions before implementation plans:
- Is this one business use case or shared domain behavior?
- Will public/dashboard flows duplicate the same rule?
- Does this need Action, Query, FormRequest, or just controller orchestration?
- What should the reviewer verify before acceptance?

Reviewers must inspect the actual diff before final verdict when safe file read access is available. If there is no read access and no diff was provided, reviewers must report the review as blocked by no read access.

Reviewers must check:
- cross-workshop leaks
- business rule placement
- duplicated domain rules across Actions
- frontend type duplication
- missing build/test validation

## Execution Policy

Default mode: SAFE FILE WORK.

Agents may:
- inspect files needed to understand the requested task
- create files
- modify files
- inspect diffs or status when needed to verify their own scoped changes

Agents must ask before running broad or destructive commands, dependency installation or updates, service startup, migrations, Docker operations, log inspection, or commands that change external state beyond the requested work.

Allowed safe inspection commands include:
- `rg`
- `sed`
- `git diff`
- `git status`
- `php artisan route:list`

Allowed validation commands when appropriate for the requested task:
- `php artisan test`
- focused `php artisan test` filters
- `npm run build` only when dependencies are already installed

Still forbidden unless the user explicitly requests the specific action:
- Docker commands
- Composer install/update
- NPM/Yarn/PNPM install/update
- service startup
- migrations
- formatters with write mode
- log inspection

For implementation tasks, use the Task Packet workflow.

After file changes:
1. Create or update `.ai/task-report.md`.
2. Report changed files.
3. Explain why each file changed.
4. Explain architecture decisions.
5. Explain tradeoffs.
6. Stop.

Do not print full file contents unless requested.

## Implementation Task Workflow

Implementation tasks should be autonomous once the user provides a Task Packet or clear task request.

Required behavior:

1. Receive a Task Packet or convert the user's request into a short working task summary.
2. Create a short plan before editing.
3. Implement only the approved or requested scope.
4. Perform self-review using the `autoservice-reviewer` rules.
5. Create or update `.ai/task-report.md`.
6. Stop and report a concise summary.

Do not continue into extra features after finishing the requested task.
Do not ask the user to copy information between chat, assistant, and Codex when the agent can write the plan, report, or workflow file directly.

Do not run tests unless the user requested validation or the task explicitly calls for it. If tests should be run later, list the exact command under the Tests section in `.ai/task-report.md`.

If a Laravel, PHP, architecture, database, backend, Vue, TypeScript, or Inertia concept may be useful for learning, suggest a `docs/learning/<topic>.md` note in the Follow Ups section. Do not create the learning note unless requested.

## Standard Task Packet

```md
# Task Packet

## Goal

## Scope

## Files Likely Affected

## Acceptance Criteria

## Tests

## Out of Scope
```

## Standard Task Report

```md
# Task Report

## Goal

## Files Changed

## Implementation Summary

## Architecture Decisions

## Tradeoffs

## Tests

## Risks

## Follow Ups
```

## Architecture Standard

Default backend flow:

```txt
Controller -> FormRequest -> Action -> Model/DB
```

Controllers coordinate HTTP only.
FormRequests validate and authorize request input when appropriate.
Actions execute business use cases.
Actions own business transactions.
Models describe persistence behavior and relationships.

Do not put business transactions in controllers.
Do not use controllers for direct multi-model business workflows.
Do not create broad god services that collect unrelated use cases.

## Backend Responsibility Split

Controller:
- HTTP orchestration only
- call FormRequest
- call Action
- write session only when it is an HTTP/session concern
- redirect or render response
- no business transactions
- no `DB::transaction`
- no direct multi-model business workflows

FormRequest:
- validation
- request-level authorization when appropriate
- input preparation only when it is request-shaping, not business workflow
- no persistence
- no business workflows

Action:
- one business use case
- transactional operations
- coordinates model writes
- may use `DB::transaction` when one business operation writes multiple records
- returns result to controller

Query/read class:
- use for non-trivial read flows, reusable reads, filtering, pagination, or heavy presentation mapping
- do not create query classes for tiny read-only pages that remain clear in the controller
- never put non-trivial Eloquent queries, eager loading, sorting chains, or DTO/presentation mapping in controllers
- if a controller needs a nested payload such as customers with vehicles, move that read and mapping into a Query/read class

Model:
- relationships
- casts
- fillable or guarded
- local scopes if useful
- enum behavior that belongs to the enum/model
- no large business workflows

Policy:
- authorization decisions when needed
- do not create a policy unless the feature needs authorization beyond simple route/auth guard

## Frontend Component Structure

Page components should orchestrate layout and pass props.
Large tables, lists, cards, and modals must be extracted into feature components.
Do not create god components.
Components should contain component things: template, local UI state, event handlers, and small computed values.
Move reusable or bulky TypeScript types into nearby `type.ts` or existing feature type files instead of declaring them inside Vue components.
Do not introduce stores for page-local server props.
Use stores only for shared application state such as auth user, active workshop, theme, locale, sidebar, and notifications.
Modal and popup components must live near the feature they belong to, for example `components/dashboard/modals/`.
Shared reusable UI belongs in existing common component folders only when reused by multiple features.

## Active Workshop Rule

Queries and authorization must be scoped by active Workshop membership.

Do not use direct `user.workshop_id`.
Resolve workshop access through `WorkshopUser`.
Use active workshop membership as the source of truth for workshop-scoped queries, permissions, and business actions.

## Scope Control

Implement only the requested task.

Do not introduce:
- unrelated refactoring
- additional architecture layers
- speculative abstractions
- future features

If a potential improvement is discovered:
- mention it in Tradeoffs or Follow Ups
- do not implement it unless requested

## Change Reporting

After every task:
1. Create or update `.ai/task-report.md` for implementation tasks.
2. List changed files.
3. Explain why each file changed.
4. Explain architecture decisions.
5. Explain tradeoffs.
6. Stop.

Do not print full file contents unless requested.

## Learning Workflow

When a Laravel, PHP, architecture, database, backend, Vue, TypeScript, or Inertia concept causes confusion during implementation, suggest creating:

```txt
docs/learning/<topic>.md
```

Learning notes should:
- use examples from the current AutoService project
- explain concepts in practical terms
- include implementation examples from the codebase
- avoid copying framework documentation
- include common mistakes and self-check questions

## Explain Decisions

When introducing:
- Laravel features
- Vue/Inertia features
- TypeScript patterns
- framework abstractions
- architectural patterns

briefly explain:
- what it is
- why it is used
- why it is better than the obvious alternative

Do not assume prior knowledge.

Do not restate approved plans in full.
Implementation prompts should reference the approved plan and contain only task-specific instructions.

### Default Behavior

For non-trivial tasks, do not jump directly to implementation.

Use:
1. plan;
2. smallest useful slice;
3. implementation;
4. verification;
5. task report;
6. lesson update if the user corrected something reusable.

### User Corrections

When the user corrects product direction, architecture, workflow, or implementation approach, decide whether it should be added to `.ai/lessons/autoservice.md`.
Do not make the user repeat the same correction in future tasks.
