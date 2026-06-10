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

For controller/action/query responsibility rules, follow:

- `.agents/skills/autoservice-controller-flow.md`

For architecture review, follow:

- `.agents/skills/laravel-grasp-solid-review.md`

## Execution Policy

Default mode: FILE CHANGES ONLY.

Agents may:
- create files
- modify files

Agents must not run commands unless the user explicitly says `EXECUTION MODE`.

Forbidden by default:
- Docker commands
- Composer commands
- NPM, Yarn, or PNPM commands
- Artisan commands
- tests
- service startup
- logs inspection

After file modifications, agents must stop and report changed files.

## Architecture Standard

Default flow:

Controller -> FormRequest -> Action -> Model/DB

Controllers coordinate HTTP only.
FormRequests validate and authorize request input when appropriate.
Actions execute business use cases.
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

## Default Agent Mode

Default mode is FILE CHANGES ONLY.
Agents may create and modify files.

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
After file changes, stop and report changed files.
