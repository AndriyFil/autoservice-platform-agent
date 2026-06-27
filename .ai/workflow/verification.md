# Verification Workflow

Verification proves the work matches the request before the agent stops.

## Default Verification

For every non-trivial task:

1. Inspect the changed files.
2. Check the diff when command access is allowed.
3. Confirm the change stays inside the requested scope.
4. Confirm no application code changed for documentation-only tasks.
5. Update `.ai/task-report.md`.

## Reviewer Checks

Self-review using the AutoService reviewer rules:

- cross-workshop leaks
- misplaced business rules
- duplicated domain rules across Actions
- frontend type duplication
- missing build or test validation
- files changed outside scope

For documentation-only tasks, focus on:

- clear workflow instructions
- no contradictions with AGENTS.md
- no new unnecessary process burden
- no accidental application-code changes

## Tests And Commands

Do not run tests unless the user asks for validation that includes tests.

If tests should be run later, list the exact command in `.ai/task-report.md` under Tests.

Ask before broad or destructive commands, dependency installation or updates, service startup, migrations, Docker operations, or log inspection.

## Stop Condition

Stop after the requested scope is complete, verified, and reported. Put possible future improvements in the task report Follow Ups instead of implementing them.
