# Execution Workflow

Execution should follow the plan and stay inside the requested scope.

## Core Rules

- Implement the smallest useful slice.
- Prefer Laravel, Vue, Inertia, and project conventions already present.
- Do not introduce a pattern unless it reduces real complexity, duplication, unclear responsibility, risk of change, or testing difficulty.
- Do not modify unrelated files.
- Do not continue into extra features after the requested task is complete.

## Backend Work

Default flow:

```txt
Controller -> FormRequest -> Action -> Model/DB
```

Controllers coordinate HTTP only. FormRequests validate and authorize request input. Actions execute business use cases and own transactions. Models describe persistence behavior and relationships.

Use Query/read classes for non-trivial read flows, reusable reads, filtering, pagination, eager loading, or heavy presentation mapping.

## Frontend Work

Page components should orchestrate layout and pass props. Extract large tables, lists, cards, modals, and repeated UI into feature components.

Keep bulky or reusable TypeScript types in nearby `type.ts` files or existing feature type files. Do not create stores for page-local server props.

## Documentation Work

Documentation changes should explain operational behavior clearly and should not modify application code unless explicitly requested.

When documenting decisions, capture:

- what changed
- why it changed
- how agents should apply it next time
- any tradeoffs or risks

## Command Safety

Normal safe work may include file inspection and file edits needed for the task.

Ask before broad or destructive commands, dependency installation or updates, Docker operations, service startup, migrations, log inspection, or any command that changes external state beyond the requested work.
