# AutoService DDD Rules

This is the compact architecture reference for future Codex tasks. Use it to keep focused changes aligned with the current AutoService direction.

## Architecture Style

AutoService is a Laravel + Inertia + Vue modular monolith.

Use practical DDD-lite: group business behavior by domain context, keep HTTP concerns in Laravel HTTP classes, and avoid pure academic DDD ceremony unless explicitly requested.

Current structure rules:

- Eloquent models stay in `app/Models`.
- HTTP controllers and requests stay in `app/Http`.
- Business logic lives in `app/Domain/{Context}`.
- Do not add generic repositories, persistence mappers, duplicated domain entities, CQRS, event sourcing, or microservice-style boundaries by default.

Patterns must pay rent. Add a pattern only when it reduces real complexity, duplication, unclear responsibility, risk of change, or testing difficulty.

## Database

AutoService is PostgreSQL-only.

- Do not add MySQL, SQLite, or cross-database migration branches unless explicitly requested.
- Do not use `DB::getDriverName()` for PostgreSQL/MySQL branching in migrations.
- Prefer explicit PostgreSQL migrations.
- Database constraints should target PostgreSQL behavior.
- If a migration changes a constrained enum-like string column, update PostgreSQL `CHECK` constraints directly.

## Current Domains

Migrated or agreed domain contexts:

- `Workshops`
- `Customers`
- `CustomerPortal`
- `BookingRequests`
- `RepairOrders`
- `Shared/ValueObjects/Phone`

Future domain contexts:

- `Estimates`
- `Documents`
- `Notifications`

## Business Rules

- A `Customer` is not a `User`.
- `Customer` records are workshop-scoped.
- Customer Portal access verifies possession of a normalized phone through a short-lived session. It does not create Customer `User` accounts, passwords, or credentials.
- A `Vehicle` belongs to a `Customer`.
- Public intake creates a `BookingRequest`, not a `RepairOrder`.
- A `RepairOrder` is an internal workshop order.

## RepairOrder Status

Valid operational statuses:

- `draft`
- `in_progress`
- `completed`
- `cancelled`

Do not add `estimated` or `approved` as `RepairOrder` statuses.

Allowed normal transitions:

- `draft -> in_progress`
- `draft -> cancelled`
- `in_progress -> draft`
- `in_progress -> completed`
- `in_progress -> cancelled`

`completed` and `cancelled` have no normal outgoing transitions.

Estimate approval does not block moving a repair order to `in_progress` for now. Quote and approval state belongs to the future `Estimates` domain, not to `RepairOrder` operational status.

## Boundaries

- Public and Admin are UI surfaces, not domain modules.
- Public/admin controllers and pages may differ, but they must share the same domain rules.
- Customer Portal is a planned global public surface. Its access boundary is a short-lived verified-phone session; future portal reads must resolve workshop-scoped records only after phone verification and must not turn a `Customer` into a `User`.
- `Estimates` will handle quote and approval state later.
- `Notifications` will be added later when needed.
- Do not introduce RabbitMQ, Redis, Go services, or other async infrastructure yet.

## Forbidden Default Scope

Do not add these unless a task explicitly asks for them:

- Customer User accounts, customer passwords, or credential-based customer login
- Customer Portal request/detail features beyond an explicitly requested verified-phone slice
- billing
- RabbitMQ, Redis, or Go services
- Telegram or email notifications
- broad UI redesign
- moving Eloquent models out of `app/Models`
- migrating unrelated domains during focused tasks
