# Task Report

## Goal

Add local development seed data for AutoService.

## Files Changed

- `database/seeders/DatabaseSeeder.php`
- `.ai/task-report.md`

## Implementation Summary

- Replaced default starter seed user with deterministic local demo data.
- Added one owner user:
  - email: `owner@example.com`
  - password: `password`
- Added one workshop:
  - name: `Main Auto`
  - slug: `main-auto`
- Added owner membership from `owner@example.com` to `Main Auto`.
- Added five realistic demo customers.
- Added vehicles for four customers, including one customer with two vehicles.
- Added six booking requests covering `new`, `confirmed`, `rejected`, and `cancelled`.
- Included booking requests with vehicles and without vehicles.

## Architecture Decisions

- Kept all changes in `DatabaseSeeder` because the task only needs local demo data and no production behavior.
- Used `updateOrCreate` with stable keys so repeated local seeding updates demo rows instead of creating duplicate users, workshop, customers, vehicles, memberships, or booking requests.
- Used existing enums `WorkshopUserRole` and `BookingRequestStatus` so seeded values match domain values.
- Did not create factories for vehicles or booking requests because this seed data is explicit and small; extra factories would not reduce current complexity.
- Demo credentials are documented in a seeder comment and in this task report.

## Tradeoffs

- Booking request idempotence uses the tuple `workshop_id`, `customer_id`, and `problem_description` as a stable demo key because the table has no dedicated natural unique key.
- Seed dates are fixed for predictable local screens instead of relative to current date.
- Seed data is intentionally single-workshop because multi-branch and extra roles are out of scope.

## Tests

Not run. `EXECUTION MODE` was not enabled.

Suggested command for later `EXECUTION MODE`:

```sh
php artisan migrate:fresh --seed
```

## Risks

- Seeder was not executed, so runtime validation was not performed in this turn.
- Re-running `db:seed` in a non-fresh local database may update matching demo rows but will not remove unrelated local rows.

## Follow Ups

- Consider `docs/learning/laravel-seeders-and-factories.md` if seeders versus factories need a project-specific learning note.
