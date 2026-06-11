# AutoService Task Workflow

## Purpose

Use this skill for implementation tasks.

The goal is to reduce manual copy-paste between the user, assistant, and Codex by turning a Task Packet into a small plan, scoped file changes, self-review, and a reusable task report.

## Required inputs

Prefer a Task Packet.

If the user gives a normal implementation request instead of a Task Packet, infer a short working summary from the request and continue when the scope is clear.

Ask a question only when the scope is ambiguous enough that implementation would risk changing the wrong behavior.

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

## Workflow

1. Read the Task Packet or user request.
2. Create a short plan.
3. Inspect only files needed for the requested scope.
4. Implement only the approved or requested scope.
5. Self-review using `autoservice-reviewer` rules.
6. Create or update `.ai/task-report.md`.
7. Stop and report a concise summary.

## Autonomy rules

- Do not require the user to copy the plan into another prompt.
- Do not require the user to copy review notes into another prompt.
- Do not require the user to manually assemble a task report.
- Write `.ai/task-report.md` directly after implementation.
- Keep the task report practical and specific to the completed work.
- Do not continue into extra features after the acceptance criteria are met.

## Scope rules

- Implement only the requested task.
- Do not modify application code when the task is documentation or AI workflow only.
- Do not modify product documentation unless the task explicitly asks for product docs.
- Do not introduce unrelated refactoring.
- Do not create speculative abstractions.
- Mention possible improvements in Tradeoffs or Follow Ups instead of implementing them.

## Review rules

Before reporting completion, review the result using the `autoservice-reviewer` checks:

- business rule correctness
- responsibility placement
- Laravel conventions
- controller, FormRequest, Action, Model, and Policy boundaries
- active Workshop membership scoping through `WorkshopUser`
- unnecessary patterns or broad services
- tests that should be added or updated
- command execution boundaries

For documentation-only tasks, apply the same review posture to the instructions themselves:

- no invented requirements
- no architecture shortcuts
- no conflict with existing AGENTS rules
- no instruction that encourages command execution without `EXECUTION MODE`

## Execution boundary

Default mode is FILE CHANGES ONLY.

Do not run:

- Docker commands
- Composer commands
- NPM, Yarn, or PNPM commands
- Artisan commands
- tests
- service startup
- log inspection

unless the user explicitly writes:

```txt
EXECUTION MODE
```

If tests should be run, list the exact command under Tests in `.ai/task-report.md`, but do not run it.

## Task Report

Create or update:

```txt
.ai/task-report.md
```

Use this format:

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

## Learning notes

If a Laravel, PHP, architecture, database, or backend concept may be useful for learning, suggest a note under Follow Ups:

```txt
docs/learning/<topic>.md
```

Do not create the learning note unless requested.
