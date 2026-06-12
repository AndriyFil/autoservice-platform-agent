# AutoService Learning Note

## Purpose

Use this skill to create concise learning notes for concepts encountered while building AutoService.

Learning notes are for understanding, not for production architecture decisions.

## When to suggest a note

Suggest a learning note when the user is confused about a Laravel, PHP, database, architecture, Vue, TypeScript, or Inertia concept during implementation.

Do not create the note unless requested.

## Location

Create notes under:

```txt
docs/learning/<topic>.md
```

## Required structure

```md
# Topic Name

## What it is

## Where we use it in AutoService

## How it works internally

## Example from this project

## Common mistakes

## Mini exercise

## Self-check questions
```

## Rules

- Use examples from the current AutoService project.
- Explain in practical terms.
- Include SQL equivalent when useful.
- Do not copy framework documentation.
- Do not invent features that do not exist.
- If the concept is not yet used in the project, mark examples as `TODO: confirm after implementation`.
- Keep notes concise and useful for later review.

## Good topics for this project

- Route Model Binding
- Service Container
- Dependency Injection
- Form Requests
- Middleware
- Policies
- Eloquent Relationships
- Eager Loading
- Transactions
- Session flash data
- Inertia props
- Vue `defineProps`
- TypeScript `type` vs `interface`
- Feature testing
