# DDD-Lite Modular Monolith

AutoService is a Laravel monolith with Inertia and Vue. It should stay one application and one repository, but the backend should be organized by business contexts as the product grows.

This document defines the practical DDD-lite structure used by this project.

## Why DDD-lite

We use DDD-lite because AutoService has real business rules, but it does not need pure academic DDD.

Pure DDD often introduces separate domain entities, repositories, mappers, and persistence models for every concept. That can be useful in large systems with complex persistence boundaries, but it would add ceremony here before the codebase has that pain.

The AutoService rule is: patterns must pay rent. A pattern belongs in the code only when it reduces complexity, duplication, unclear responsibility, risk of change, or testing difficulty.

DDD-lite gives us the useful part:

- business behavior grouped by domain context
- thin HTTP controllers
- focused Actions for write use cases
- focused Queries for non-trivial read models
- explicit Enums, Data objects, Exceptions, and Services when they remove real ambiguity

It avoids the expensive part:

- no microservices
- no second repository
- no generic repository layer over Eloquent
- no duplicated domain entity model separate from Eloquent
- no event-sourcing or CQRS infrastructure by default

## Business Contexts, Not Technical Buckets

Modules are based on business contexts, not technical layers.

Good module names describe the business area:

- `Workshops`
- `BookingRequests`
- `Customers`
- `RepairOrders`
- `Estimates`
- `Documents`
- `Notifications`
- `Shared`

Technical folders such as Controllers, Requests, Models, and Middleware still exist, but they are not domain modules. They are framework integration points.

For example, Workshop staff management is part of the `Workshops` context because the rules are about workshop membership, owner-only access, staff roles, and last-owner protection. It should not be split into an `Admin` domain just because staff management is reached from the dashboard.

## Public And Admin Are UI Surfaces

Public and Admin are surfaces, not domains.

The public intake page and dashboard pages may show different screens, but they can use the same business concepts. A booking request is still a booking request whether it was created from a public page or managed from the dashboard.

Creating `PublicDomain` or `AdminDomain` would hide business rules behind the place where the user clicked. That increases duplication risk. Instead:

- `app/Http/Controllers` owns HTTP orchestration for public and dashboard routes
- `app/Http/Requests` owns HTTP validation and request authorization
- `app/Domain/{Context}` owns business use cases and read models

Example:

```txt
Dashboard Workshop Settings page
-> App\Http\Controllers\Dashboard\WorkshopSettingsController
-> App\Http\Requests\Workshop\UpdateWorkshopSettingsRequest
-> App\Domain\Workshops\Actions\UpdateWorkshopSettingsAction
-> App\Models\Workshop
```

The controller is dashboard-specific. The action is workshop-domain behavior.

## Why Eloquent Models Stay In app/Models

Eloquent models remain in `app/Models` for now.

Laravel expects models to be central persistence objects, and this project already uses them across factories, tests, relationships, route model binding, casts, and migrations. Moving them into domain folders now would create churn without improving the business boundary.

Keeping models in `app/Models` also avoids duplicating entities and mappers. Domain Actions can use Eloquent directly when that is the simplest correct design.

If a future context grows enough that persistence concerns make domain code hard to test or change, we can revisit that boundary. Until then, Eloquent remains the persistence model.

## Folder Structure

Domain code lives under:

```txt
app/Domain/{Context}/Actions
app/Domain/{Context}/Queries
app/Domain/{Context}/Enums
app/Domain/{Context}/Data
app/Domain/{Context}/Exceptions
app/Domain/{Context}/Services
```

Shared domain helpers live under:

```txt
app/Domain/Shared/ValueObjects
app/Domain/Shared/Exceptions
```

Use `Shared` carefully. A helper belongs there only when multiple business contexts genuinely share it. Do not move code into `Shared` just because it feels reusable.

## What Goes Where

### Actions

Actions execute one write use case.

Use Actions for business operations such as:

- updating workshop settings
- adding a staff member to a workshop
- changing a staff role
- removing a workshop membership
- preparing an estimate snapshot

Actions may:

- coordinate Eloquent writes
- own `DB::transaction`
- enforce business invariants
- return the affected model or result

Actions must not:

- render Inertia pages
- redirect
- write flash session messages
- become broad services with unrelated methods

### Queries

Queries prepare non-trivial read data.

Use Queries when a controller would otherwise contain eager loading, sorting, filtering, pagination, counts, or presentation mapping.

Examples:

- `WorkshopSettingsQuery` prepares settings page props for the active workshop
- `WorkshopStaffQuery` prepares staff rows, role labels, current-user flags, and last-owner flags

Queries should avoid N+1 problems and keep controllers focused on HTTP orchestration.

### Enums

Enums hold reusable business values.

Examples:

- `WorkshopUserRole::Owner`
- `WorkshopUserRole::Staff`

Enums belong in the domain context that owns their meaning. `WorkshopUserRole` belongs to `Workshops`, not a global technical enum folder, because the values describe workshop membership rules.

### Data

Data classes are optional typed payload objects.

Use a Data class when it improves clarity or reduces repeated array-shape knowledge across multiple callers.

Do not create a Data class only to wrap three validated fields used once by one action. In that case, a FormRequest validated array is simpler and clear enough.

### Exceptions

Domain exceptions name business-rule failures.

Examples in the Workshops context:

- `LastOwnerCannotBeRemoved`
- `LastOwnerCannotBeDemoted`
- `StaffAlreadyBelongsToWorkshop`
- `WorkshopStaffNotFound`

When a failure is user-facing form feedback, the exception may extend or convert to Laravel validation behavior so existing redirect/error handling remains intact.

### Services

Services are for focused domain operations that are reused by multiple Actions and do not naturally belong to one model.

Use Services sparingly. A service with many unrelated public methods is a god service and should be split back into Actions or smaller helpers.

## First Migrated Context: Workshops

The first migrated context is `Workshops`.

The `Workshops` domain owns:

- workshop settings
- workshop staff management
- `WorkshopUser` membership rules
- `WorkshopUserRole`
- last-owner protection
- owner-only staff/settings management

Current structure:

```txt
app/Domain/Workshops/Actions
app/Domain/Workshops/Queries
app/Domain/Workshops/Enums
app/Domain/Workshops/Exceptions
app/Domain/Workshops/Data
app/Domain/Workshops/Services
```

HTTP classes remain outside the domain:

```txt
app/Http/Controllers/Dashboard/WorkshopSettingsController.php
app/Http/Controllers/Dashboard/WorkshopStaffController.php
app/Http/Requests/Workshop
```

Models remain in:

```txt
app/Models/Workshop.php
app/Models/WorkshopUser.php
app/Models/User.php
```

## Future Contexts

Future migrations should be done one context at a time.

Likely next contexts:

- `BookingRequests`: public intake submission, missing-field handling, intake queue reads, conversion boundaries
- `Customers`: workshop-scoped customers, phone normalization, customer vehicle management
- `RepairOrders`: lifecycle transitions, staff work management, repair order lines
- `Estimates`: estimate snapshots, versioning, PDF preparation boundaries
- `Documents`: generated document storage and document history
- `Notifications`: future customer/staff notification delivery

Do not migrate a context only to satisfy folder symmetry. Move code when the new location makes rules easier to find, test, and protect from duplication.

## Migration Rule

Migrate by useful slices:

1. Choose one business context.
2. Move only the Actions, Queries, Enums, Data objects, Exceptions, or Services that context already needs.
3. Keep route behavior stable.
4. Keep active workshop isolation through `WorkshopUser`.
5. Update tests and imports.
6. Stop.

This keeps the monolith modular without turning the migration into a rewrite.
