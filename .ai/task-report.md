# Task Report

## Goal

Add Customer admin section for the active workshop.

## Files Changed

- `routes/web.php`
- `app/Http/Controllers/CustomerController.php`
- `app/Queries/Customers/CustomerListQuery.php`
- `app/Queries/Customers/CustomerDetailsQuery.php`
- `resources/js/pages/Customers/Index.vue`
- `resources/js/pages/Customers/Show.vue`
- `resources/js/components/customers/CustomerTable.vue`
- `resources/js/components/customers/CustomerEmptyState.vue`
- `resources/js/components/customers/CustomerBookingRequests.vue`
- `resources/js/components/customers/CustomerVehicles.vue`
- `resources/js/components/customers/types.ts`
- `resources/js/components/customers/utils.ts`
- `resources/js/components/AppSidebar.vue`
- `resources/js/components/NavMain.vue`
- `tests/Feature/CustomerManagementTest.php`
- `.ai/task-report.md`

## Implementation Summary

- Added authenticated `/customers` and `/customers/{customer}` routes.
- Added a customer controller that resolves active workshop membership and redirects users without membership to workshop onboarding.
- Added customer list/detail query classes that scope all reads by `WorkshopUser::workshop_id`.
- Added customer list and detail Inertia pages.
- Extracted customer table, vehicles, booking requests, empty state, types, and formatting helpers into feature components.
- Added Customers navigation entry and fixed main nav to use the shared `href` nav shape.
- Added feature tests for guest redirects, onboarding redirect, active workshop list scoping, detail access, cross-workshop 404, and detail payload contents.

## Architecture Decisions

- No FormRequest was added because customer pages are read-only and accept no input needing validation.
- No Action was added because there is no business write use case or transaction.
- Query classes own list/detail mapping because customer counts, latest booking date, vehicles, booking requests, and status labels would make the controller too busy.
- Active workshop access is resolved through `ActiveWorkshopMembershipResolver` and `WorkshopUser`; no direct `user.workshop_id` usage was introduced.
- Cross-workshop detail access uses scoped query plus `firstOrFail()`, producing a 404 without a broad policy or service.
- Frontend pages orchestrate layout and props; reusable customer UI lives under `resources/js/components/customers`.

## Tradeoffs

- Customer list has no pagination/search/filter because those were out of scope; this is acceptable for current requested admin section but may need pagination as data grows.
- Detail query returns all vehicles and booking requests for the customer; this keeps the page simple now, but pagination may be needed later for high-volume customers.
- A small query layer was added instead of controller Eloquent mapping to reduce controller responsibility without introducing repositories or broad services.

## Tests

Not run. `EXECUTION MODE` was not enabled.

Suggested command for later `EXECUTION MODE`:

```sh
php artisan test --filter=CustomerManagementTest
```

## Risks

- Frontend build and feature tests were not executed due to file-only execution policy.
- Large customer histories may create heavy detail payloads until pagination is added.

## Follow Ups

- Consider `docs/learning/active-workshop-scoping.md` to explain how `WorkshopUser` scopes admin pages in this project.
- Add customer pagination/search/filter only when requested or when list size makes it necessary.
