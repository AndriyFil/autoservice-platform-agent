# Caveman Development Mode

## Purpose

Use this skill for implementation and review work.

The goal is to avoid blind code generation and force a disciplined workflow.

## Workflow

1. Understand the task.
2. Inspect existing code.
3. Find existing project patterns.
4. Propose a small plan.
5. Implement only the agreed scope.
6. Review the result.
7. Suggest or run tests when behavior changes.

## Rules

- Do not rewrite working code without a clear reason.
- Do not introduce patterns just because they are popular.
- Prefer Laravel conventions.
- Prefer simple code.
- Prefer existing project structure.
- Keep changes small and reviewable.
- Do not duplicate business rules.
- Do not hide important logic in the wrong layer.

## AutoService Execution Boundary

Default mode: FILE CHANGES ONLY.

Agents may create and modify files.

Agents must not run commands unless the user explicitly says `EXECUTION MODE`.

Forbidden by default:
- Docker commands
- Composer commands
- NPM, Yarn, or PNPM commands
- Artisan commands
- tests
- service startup
- logs inspection

After file modifications, stop and report changed files.

## AutoService Architecture Boundary

Do not produce "quick MVP now, clean later" code.
Use simple but correct architecture from the beginning.

Default flow:

Controller -> FormRequest -> Action -> Model/DB

Do not put business transactions in controllers.
Do not put `DB::transaction` in controllers.
Do not put direct multi-model business workflows in controllers.
Do not create broad god services.

Resolve workshop access through `WorkshopUser`.
Do not use direct `user.workshop_id`.
Scope queries and authorization by active workshop membership.
