# AutoService Agent Workflow

This folder documents how AutoService agents should work inside this project.

The goal is simple: make agent work predictable, reviewable, and easy to improve over time. Agents should plan before non-trivial work, implement the smallest useful slice, verify the result, record what changed, and preserve reusable lessons from user corrections.

## Workflow Files

- `planning.md` explains how to turn a request into a small working plan.
- `execution.md` explains how to make scoped file changes without drifting into unrelated work.
- `verification.md` explains self-review and validation expectations.
- `lessons.md` explains how corrections become reusable project memory.
- `.ai/lessons/autoservice.md` stores project-specific lessons agents must check before non-trivial work.

## Default Loop

For non-trivial tasks:

1. Check `.ai/lessons/autoservice.md`.
2. Convert the request into a short task summary or Task Packet.
3. Plan the smallest useful slice.
4. Implement only the requested scope.
5. Verify through inspection and allowed validation.
6. Update `.ai/task-report.md`.
7. Stop.

## Safety

Normal safe work should not require the user to type special mode names. Agents may inspect and edit files needed for the task.

Agents must still ask before broad or destructive commands, dependency installation or updates, service startup, migrations, Docker operations, log inspection, or any action that changes external state outside the requested file changes.
