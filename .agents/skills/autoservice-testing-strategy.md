# AutoService Testing Strategy

## Purpose

Use this skill when planning, writing, or reviewing tests.

The goal is to test behavior that must not break, not to chase line coverage.

## Feature test questions

For every feature, ask:

1. Who has access?
2. Which data should they access?
3. What should happen in the happy path?
4. What must be forbidden?
5. What edge cases can break the business rule?

## Test categories

Access:
- guest redirects to login when route requires auth
- user without workshop membership redirects to onboarding or is denied
- user with membership can access own workshop data

Scope/isolation:
- active workshop data is visible
- other workshop data is hidden or returns 404
- direct ID guessing does not expose cross-workshop records

Business behavior:
- creation/update/status transition happens as expected
- forbidden status transitions fail
- snapshots are stored when required
- existing records are reused when required
- existing records are not overwritten when rules forbid it

Data shape:
- Inertia props contain only required fields
- enum values and labels are represented consistently
- nullable relationships are represented clearly

Ordering:
- test ordering only when order matters to UI or behavior

## What not to test

Do not test:

- private methods directly
- Laravel internals
- Eloquent `create` itself
- CSS styling details
- every markup line
- implementation details when observable behavior is enough

## Test design rule

One test should have one clear reason to fail.

When a test fails, it should be obvious whether the break is:

- access
- scope
- happy path
- forbidden behavior
- data shape
- ordering

## Database rule

Use a separate testing database.
Do not run `RefreshDatabase` against the local development database.

Recommended testing env:

```env
APP_ENV=testing
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=autoservice_testing
DB_USERNAME=autoservice
DB_PASSWORD=autoservice
SESSION_DRIVER=array
CACHE_STORE=array
QUEUE_CONNECTION=sync
```
