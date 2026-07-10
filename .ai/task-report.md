# Task Report

## Goal

Continue the practical DDD-lite modular monolith migration by moving the Customers business slice into `app/Domain/Customers` and moving shared phone normalization into `app/Domain/Shared/ValueObjects`.

## Files Changed

- `app/Domain/Shared/ValueObjects/Phone.php`
- `app/Domain/Customers/Actions/CreateCustomerVehicleAction.php`
- `app/Domain/Customers/Actions/UpdateCustomerAction.php`
- `app/Domain/Customers/Actions/UpdateCustomerVehicleAction.php`
- `app/Domain/Customers/Data/.gitkeep`
- `app/Domain/Customers/Exceptions/.gitkeep`
- `app/Domain/Customers/Queries/CustomerIndexQuery.php`
- `app/Domain/Customers/Queries/CustomerShowQuery.php`
- `app/Http/Controllers/CustomerController.php`
- `app/Models/Customer.php`
- `app/Models/BookingRequest.php`
- `app/Actions/BookingRequests/CreatePublicBookingRequestAction.php`
- `app/Actions/BookingRequests/ResolveBookingRequestCustomerAction.php`
- `app/Actions/BookingRequests/SubmitIntakeRequestAction.php`
- `app/Actions/RepairOrders/CreateRepairOrderFromBookingRequestAction.php`
- `app/Queries/Dashboard/DashboardBookingRequestDetailsQuery.php`
- `app/Queries/Dashboard/DashboardRepairOrderFormQuery.php`
- `app/Http/Requests/StorePublicIntakeRequest.php`
- `app/Support/Intake/ManualFallbackIntakeExtractor.php`
- `database/factories/CustomerFactory.php`
- `database/factories/BookingRequestFactory.php`
- `database/seeders/DatabaseSeeder.php`
- `tests/Unit/PhoneTest.php`
- `tests/Feature/CustomerManagementTest.php`
- Deleted old paths under `app/Actions/Customers`, `app/Queries/Customers`, and `app/Support/Phone.php`

## Implementation Summary

Moved the shared `Phone` value object from `App\Support` to `App\Domain\Shared\ValueObjects` without changing normalization behavior.

Moved customer update and vehicle create/update use cases into `App\Domain\Customers\Actions`. These Actions keep active-workshop checks, prevent cross-workshop mutation, prevent moving vehicles between customers, keep customer phone normalization in sync, and block duplicate customer phones inside the same workshop.

Moved customer list/show read flows into `App\Domain\Customers\Queries` as `CustomerIndexQuery` and `CustomerShowQuery`. The index query still scopes to the active workshop, keeps counts/latest booking request data, and now normalizes formatted phone search input before searching normalized columns. The show query eager loads vehicles, booking requests, and repair orders with vehicles while scoping customer-owned records to the active workshop.

Updated imports in controllers, models, factories, seeders, tests, booking-request conversion, repair-order creation, public intake validation, and dashboard queries.

Added regression coverage for formatted phone search and blocking vehicle creation for a customer outside the active workshop.

## Architecture Decisions

Controllers remain HTTP orchestration only. `CustomerController` resolves the active workshop user and delegates writes to `Domain\Customers\Actions` and reads to `Domain\Customers\Queries`.

Eloquent models stayed in `app/Models` as required. The customer domain classes coordinate use cases around those models without adding repositories or mappers.

`Phone` lives in `Domain\Shared\ValueObjects` because phone normalization is used by Customers, BookingRequests, public intake, factories, and repair-order conversion.

Customer remains distinct from User. No customer action creates or mutates `User` records.

## Tradeoffs

Data classes were deferred. The current customer and vehicle payloads are small validated arrays used once per Action, so DTOs would add churn without reducing duplication or risk.

Domain exceptions were deferred. Existing customer form flows already use `ValidationException` for duplicate phone errors and `firstOrFail()`/404 behavior for cross-workshop access. Adding custom exceptions now would mostly require adapter code without improving the current HTTP behavior.

No `Services` folder was created because no shared customer service was needed.

`email`, `notes`, and `vin` were not added because the current `customers` and `vehicles` schemas/models do not expose those fields.

## Tests

Passed:

```sh
php -l app/Domain/Shared/ValueObjects/Phone.php
php -l app/Domain/Customers/Actions/UpdateCustomerAction.php
php -l app/Domain/Customers/Actions/CreateCustomerVehicleAction.php
php -l app/Domain/Customers/Actions/UpdateCustomerVehicleAction.php
php -l app/Domain/Customers/Queries/CustomerIndexQuery.php
php -l app/Domain/Customers/Queries/CustomerShowQuery.php
php -l app/Http/Controllers/CustomerController.php
php -l tests/Feature/CustomerManagementTest.php
php artisan test tests/Unit/PhoneTest.php tests/Feature/CustomerManagementTest.php
php artisan test tests/Feature/RepairOrderManagementTest.php tests/Feature/DashboardBookingRequestManagementTest.php tests/Feature/PublicBookingRequestFlowTest.php
```

Focused behavior results:

- `PhoneTest` and `CustomerManagementTest`: 24 tests, 177 assertions passed.
- Repair-order and booking-request related suites: 79 tests, 877 assertions passed.

## Risks

Only the focused customer, phone, repair-order, and booking-request suites were run, not the full backend test suite.

The old customer action/query and support `Phone` files were deleted rather than kept as wrappers. Current local references were updated and checked with `rg`, but any untracked external code importing old namespaces would need the new imports.

## Follow Ups

- Run the full backend suite before merging if this branch includes broader unrelated changes.
- Consider `docs/learning/laravel-domain-modules.md` if future agents need a practical learning note for DDD-lite Laravel module boundaries.
