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

## Required workflow

For development and review work, follow:

- `.agents/skills/caveman-development-mode.md`
- `.agents/skills/autoservice-task-workflow.md`

For controller/action/query responsibility rules, follow:

- `.agents/skills/autoservice-controller-flow.md`

For architecture review, follow:

- `.agents/skills/laravel-grasp-solid-review.md`

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
EXECUTION MODE

For implementation tasks, use the Task Packet workflow below.

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

If a Laravel, PHP, architecture, database, or backend concept may be useful for learning, suggest a `docs/learning/<topic>.md` note in the Follow Ups section. Do not create the learning note unless requested.

Standard Task Packet format:

```md
# Task Packet

## Goal

## Scope

## Files Likely Affected

## Acceptance Criteria

## Tests

## Out of Scope
```

Standard Task Report format:

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

Default flow:

Controller -> FormRequest -> Action -> Model/DB

Controllers coordinate HTTP only.
FormRequests validate and authorize request input when appropriate.
Actions execute business use cases.
Actions own business transactions.
Models describe persistence behavior and relationships.

Do not put business transactions in controllers.
Do not use controllers for direct multi-model business workflows.
Do not create broad god services that collect unrelated use cases.

## Responsibility Split

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
- no persistence
- no business workflows

Action:
- one business use case
- transactional operations
- coordinates model writes
- may use `DB::transaction` when one business operation writes multiple records
- returns result to controller

Model:
- relationships
- casts
- fillable or guarded
- local scopes if useful
- no large business workflows

Policy:
- authorization decisions when needed
- do not create a policy unless the feature needs authorization beyond simple route/auth guard

## Active Workshop Rule

Queries and authorization must be scoped by active Workshop membership.

Do not use direct `user.workshop_id`.
Resolve workshop access through `WorkshopUser`.
Use active workshop membership as the source of truth for workshop-scoped queries, permissions, and business actions.



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

When a Laravel, PHP, architecture, database, or backend concept causes confusion during implementation, suggest creating:

docs/learning/<topic>.md

Learning notes should:
- use examples from the current AutoService project
- explain concepts in practical terms
- include implementation examples from the codebase
- avoid copying framework documentation
- include common mistakes and self-check questions

## Scope Control

Implement only the requested task.

Do not introduce:
- unrelated refactoring
- additional architecture layers
- speculative abstractions
- future features

If a potential improvement is discovered:
- mention it in tradeoffs
- do not implement it unless requested

## Explain Decisions

When introducing:
- Laravel features
- framework abstractions
- architectural patterns
briefly explain:
- what it is
- why it is used
- why it is better than the obvious alternative
Do not assume prior knowledge.
