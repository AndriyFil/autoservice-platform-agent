# AutoService Platform AI Instructions

## Project approach

This is a Laravel AutoService / booking platform.

Use Laravel conventions first.
Use GRASP and SOLID as thinking tools, not as dogma.
Do not introduce architecture patterns unless they solve a visible problem.

## Required workflow

For development and review work, follow:

- `.agents/skills/caveman-development-mode.md`

For controller/action/query responsibility rules, follow:

- `.agents/skills/autoservice-controller-flow.md`

For architecture review, follow:

- `.agents/skills/laravel-grasp-solid-review.md`

## Main rule

Patterns must pay rent.

A pattern is acceptable only if it reduces:
- complexity
- duplication
- unclear responsibility
- risk of change
- testing difficulty

## Project rules

- Queries and authorization must be scoped by active Workshop membership.
- Do not use direct `user.workshop_id`.
- Resolve role through `WorkshopUser`.

## Agent Execution Policy

Default mode: DESIGN ONLY

Agents may create and modify files.

Agents must not:

- run docker commands
- run composer commands
- run npm/yarn commands
- run artisan commands
- run tests
- start services

unless the user explicitly enables EXECUTION MODE.

After file modifications, agents must stop and wait for review.
