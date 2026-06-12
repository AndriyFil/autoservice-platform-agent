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

## Agent roles

Use roles intentionally:

- `autoservice-architect`: plans architecture and responsibility boundaries. Does not implement unless explicitly asked.
- `autoservice-backend-lead`: implements Laravel/backend work with senior-level maintainability standards.
- `autoservice-frontend-lead`: implements Vue/Inertia frontend work with senior-level component/state discipline.
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

## Required skills

For implementation workflow, follow:

- `.agents/skills/caveman-development-mode.md`
- `.agents/skills/autoservice-task-workflow.md`

For backend responsibility boundaries, follow:

- `.agents/skills/autoservice-controller-flow.md`
- `.agents/skills/autoservice-testing-strategy.md`

For frontend structure, follow:

- `.agents/skills/autoservice-frontend-structure.md`

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

Reviewers must inspect the actual diff before final verdict when `EXECUTION MODE` allows read access. If there is no `EXECUTION MODE` and no diff was provided, reviewers must report the review as blocked by no read access.

Reviewers must check:
- cross-workshop leaks
- business rule placement
- duplicated domain rules across Actions
- frontend type duplication
- missing build/test validation

## Execution Policy

Default mode: FILE CHANGES ONLY.

Agents may:
- create files
- modify files

Agents must not run:
- Docker commands
- Composer commands
- NPM/Yarn/PNPM commands
- Artisan commands
- tests
- service startup
- log inspection

unless the user explicitly writes:

```txt
EXECUTION MODE
```

Without `EXECUTION MODE`, agents must not run shell commands. They may only create or modify files needed for the requested task.

With `EXECUTION MODE`, agents may run read-only inspection commands:
- `rg`
- `sed`
- `git diff`
- `git status`
- `php artisan route:list`

With `EXECUTION MODE`, agents may run validation commands:
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

If tests should be run, list the exact command under the Tests section in `.ai/task-report.md`, but do not run it unless the user explicitly writes `EXECUTION MODE`.

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
