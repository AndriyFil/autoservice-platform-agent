# ADR-001: MVP Domain Database Model

## Status

Accepted

## Context

AutoService is a multi-workshop SaaS platform for small and medium auto workshops.

The MVP must support:

- platform users who can access one or more workshops
- workshop-scoped owner and staff roles
- active workshop context for dashboard access and authorization
- public booking requests through a workshop-specific URL
- workshop-scoped customers, vehicles, and booking requests

The MVP intentionally excludes billing, invoices, payments, inventory, accounting, multi-branch support, and advanced scheduling.

## Decision

Use six MVP database tables:

- `users`
- `workshops`
- `workshop_users`
- `customers`
- `vehicles`
- `booking_requests`

Use PHP Enums for fixed values:

- `WorkshopUserRole`: `owner`, `staff`
- `BookingRequestStatus`: `new`, `confirmed`, `rejected`, `cancelled`

Do not create database tables for roles or booking request statuses.

### User

`User` is a platform account.

The `users` table must not contain `workshop_id`.

A user may belong to multiple workshops through `workshop_users`.

### Workshop

`Workshop` is one auto workshop business.

The `workshops` table includes:

- `name`
- `slug`

The public booking URL resolves a workshop by `workshops.slug`.

### WorkshopUser

`WorkshopUser` represents membership between a user and a workshop.

The `workshop_users` table stores:

- `user_id`
- `workshop_id`
- `role`

The pair `workshop_id` + `user_id` must be unique.

The `role` value is scoped to that workshop membership and uses `WorkshopUserRole`.

### Active Workshop Context

For MVP, active workshop id is stored in the Laravel session.

All dashboard queries are scoped by active workshop.

Authorization checks must verify that the user has `WorkshopUser` membership for the active workshop.

The user's role is resolved from `WorkshopUser.role` for the active workshop.

### Customer

`Customer` belongs to one workshop.

Customer identity inside a workshop is:

- `workshop_id` + `normalized_phone`

Existing customer name must not be automatically overwritten by later public booking requests.

### Vehicle

`Vehicle` belongs to one workshop and one customer.

`license_plate` is optional and not unique for MVP.

### BookingRequest

`BookingRequest` belongs to one workshop and one customer.

`BookingRequest` may belong to one vehicle.

`created_by_user_id` is nullable:

- `null` means public customer submission
- non-null means dashboard-created by owner or staff

`BookingRequest` always stores submitted customer snapshot fields:

- `customer_name`
- `customer_phone`

`preferred_date` is nullable and uses a date only, not datetime.

`status` uses `BookingRequestStatus`.

### Delete Behavior

Use these foreign key delete behaviors:

- `workshop_users.workshop_id`: `cascadeOnDelete`
- `workshop_users.user_id`: `cascadeOnDelete`
- `customers.workshop_id`: `restrictOnDelete`
- `vehicles.workshop_id`: `restrictOnDelete`
- `vehicles.customer_id`: `restrictOnDelete`
- `booking_requests.workshop_id`: `restrictOnDelete`
- `booking_requests.customer_id`: `restrictOnDelete`
- `booking_requests.vehicle_id`: `nullOnDelete`
- `booking_requests.created_by_user_id`: `nullOnDelete`

## Consequences

The platform account model stays separate from workshop membership.

Workshop-scoped authorization is explicit and must always use active workshop membership.

Dashboard, customer, vehicle, and booking request queries must be scoped by active workshop.

Customer lookup is deterministic within a workshop by normalized phone.

Booking requests keep historical submitted customer name and phone snapshots even if the related customer record changes later.

Vehicle deletion does not delete historical booking requests because `booking_requests.vehicle_id` is nullable and uses `nullOnDelete`.

User deletion does not delete dashboard-created booking requests because `booking_requests.created_by_user_id` is nullable and uses `nullOnDelete`.

Workshop, customer, vehicle, and booking request history is protected by restrictive delete behavior.

## Alternatives Considered

### Direct `users.workshop_id`

Rejected.

This would make a user belong to only one workshop and would conflict with the accepted membership model.

### Role Stored Directly On User

Rejected.

Role is workshop-specific and must be stored on `WorkshopUser`.

### Role And Status Lookup Tables

Rejected for MVP.

PHP Enums are simpler and match the fixed MVP values.

### Public Booking Identifier Separate From Workshop Slug

Rejected for MVP.

Using `workshops.slug` is simpler and sufficient for the public booking URL.

### Unique Vehicle License Plate

Rejected for MVP.

`license_plate` is optional and not unique.

## Notes For Implementation

Do not add `workshop_id` to `users`.

Resolve active workshop id from the Laravel session for MVP.

Resolve role through `WorkshopUser` for the active workshop.

Scope dashboard queries by active workshop.

Scope customer, vehicle, and booking request queries by active workshop.

Normalize customer phone before customer lookup or creation.

During public booking submission:

1. Resolve workshop by `workshops.slug`.
2. Find customer by `workshop_id` + `normalized_phone`.
3. Create customer if missing.
4. Create booking request.
5. Link booking request to customer.

When a later public request matches an existing customer, do not automatically overwrite the existing customer name.

Use migration order:

1. `users`
2. `workshops`
3. `workshop_users`
4. `customers`
5. `vehicles`
6. `booking_requests`
