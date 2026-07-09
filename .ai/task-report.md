# Task Report

## Goal

Introduce a practical DDD-lite modular monolith structure and migrate the Workshop Admin backend slice into the `Workshops` domain without changing public/admin route behavior or moving Eloquent models.

## Files Changed

- `docs/architecture/ddd-lite-modular-monolith.md`
- `app/Domain/Workshops/Actions/AddWorkshopStaffAction.php`
- `app/Domain/Workshops/Actions/CreateInitialWorkshopAction.php`
- `app/Domain/Workshops/Actions/RemoveWorkshopStaffAction.php`
- `app/Domain/Workshops/Actions/UpdateWorkshopSettingsAction.php`
- `app/Domain/Workshops/Actions/UpdateWorkshopStaffRoleAction.php`
- `app/Domain/Workshops/Queries/WorkshopSettingsQuery.php`
- `app/Domain/Workshops/Queries/WorkshopStaffQuery.php`
- `app/Domain/Workshops/Enums/WorkshopUserRole.php`
- `app/Domain/Workshops/Exceptions/LastOwnerCannotBeDemoted.php`
- `app/Domain/Workshops/Exceptions/LastOwnerCannotBeRemoved.php`
- `app/Domain/Workshops/Exceptions/StaffAlreadyBelongsToWorkshop.php`
- `app/Domain/Workshops/Exceptions/WorkshopStaffNotFound.php`
- `app/Domain/Workshops/Data/.gitkeep`
- `app/Domain/Workshops/Services/.gitkeep`
- `app/Domain/Shared/ValueObjects/.gitkeep`
- `app/Domain/Shared/Exceptions/.gitkeep`
- `app/Http/Controllers/Dashboard/WorkshopSettingsController.php`
- `app/Http/Controllers/Dashboard/WorkshopStaffController.php`
- `app/Http/Controllers/WorkshopOnboardingController.php`
- `app/Http/Requests/Workshop/StoreWorkshopStaffRequest.php`
- `app/Http/Requests/Workshop/UpdateWorkshopStaffRoleRequest.php`
- `app/Http/Requests/Workshop/WorkshopOwnerRequest.php`
- `app/Models/WorkshopUser.php`
- `database/factories/WorkshopUserFactory.php`
- `database/seeders/DatabaseSeeder.php`
- Workshop-role imports in existing feature tests that create `WorkshopUser` memberships
- Deleted old Workshop-specific paths under `app/Actions/Workshops`, `app/Queries/Workshops`, and `app/Enums/WorkshopUserRole.php`

## Implementation Summary

Added the DDD-lite architecture document explaining why AutoService uses business-context modules inside one Laravel app instead of pure DDD, microservices, or UI-surface domains.

Moved the Workshops slice to `app/Domain/Workshops`: staff/settings Actions, settings/staff Queries, and `WorkshopUserRole`. Updated imports in controllers, requests, model casts/helpers, factories, seeders, and tests.

Added Workshops domain exceptions for last-owner protection, duplicate membership, and hidden cross-workshop staff membership lookup. The user-facing form failures still use Laravel validation behavior where existing redirects expect validation errors.

Kept Eloquent models in `app/Models` and HTTP concerns in `app/Http`. Controllers still orchestrate requests and responses; FormRequests still validate and authorize request input; Actions own the staff/settings business rules.

Added a small `WorkshopAdminTest` smoke test that confirms the enum, Action, and Query resolve from the new domain namespace.

## Architecture Decisions

`Workshops` is the first migrated business context because it owns workshop settings, staff membership rules, owner-only management, roles, and last-owner protection.

Public/Admin were not modeled as domains. Dashboard controllers remain dashboard-specific HTTP adapters, while the business rules moved under `Domain\Workshops`.

The admin Actions now re-check that the active actor is a workshop owner. The FormRequests already enforce this at the HTTP boundary, but the invariant also belongs inside the use-case boundary so direct Action use does not bypass it.

`WorkshopStaffNotFound` preserves hidden cross-workshop behavior by returning 404 semantics for membership guesses outside the active workshop.

## Tradeoffs

Data classes were deferred. The current staff/settings payloads are single-use validated arrays; adding DTOs now would mostly wrap a few fields once and would not reduce duplication or risk.

`Data` and `Services` folders were kept with `.gitkeep` files because the requested architecture includes them, but no empty abstraction was added.

Other domains were left in their existing `app/Actions`, `app/Queries`, and `app/Enums` locations. Migrating them now would exceed the requested Workshop-only scope.

The workspace already contained broader public/admin route and frontend changes before this task. Those were treated as existing work and were not reverted.

## Tests

Passed:

```sh
php -l app/Domain/Workshops/Actions/AddWorkshopStaffAction.php
php -l app/Domain/Workshops/Actions/UpdateWorkshopStaffRoleAction.php
php -l app/Domain/Workshops/Actions/RemoveWorkshopStaffAction.php
php -l app/Domain/Workshops/Actions/UpdateWorkshopSettingsAction.php
php -l app/Domain/Workshops/Exceptions/LastOwnerCannotBeDemoted.php
php -l app/Domain/Workshops/Exceptions/WorkshopStaffNotFound.php
./vendor/bin/phpstan analyse --memory-limit=1G
php artisan test tests/Feature/WorkshopAdminTest.php
```

`WorkshopAdminTest` passed 20 tests with 107 assertions.

One first PHPStan attempt failed inside the sandbox with `Failed to listen on "tcp://127.0.0.1:0": Operation not permitted (EPERM)`. The command was rerun outside the sandbox and passed with no errors.

## Risks

Because several files were already staged/modified before this task, git status shows old paths as deleted and the new `app/Domain` tree as untracked until the move is staged. Review the final staged diff carefully before committing.

Only the focused Workshop admin feature test was run, not the full backend suite.

## Follow Ups

- Before merging, stage the old-path deletions and new `app/Domain` files together so Git records the Workshop migration cleanly.
- Run the full backend suite if the broader pre-existing public/admin and frontend changes are being merged in the same branch.
- Consider `docs/learning/laravel-domain-modules.md` if future agents need a teaching note on Laravel DDD-lite boundaries.
