# MVP Scope

## Workshop

Workshop is a real MVP entity.

Represents one auto workshop business.

Workshop has many:

- workshop memberships
- customers
- vehicles
- booking requests

## User

User is a platform account.

User does not belong directly to a workshop.

One user may belong to multiple workshops through workshop membership.

## WorkshopUser

WorkshopUser represents membership between user and workshop.

WorkshopUser belongs to:

- one user
- one workshop

WorkshopUser has one workshop-specific role:

- owner
- staff

Authorization is evaluated in the context of the active workshop.

Owner can:

- view dashboard
- view booking requests
- create booking requests from dashboard
- confirm booking requests
- reject booking requests
- cancel booking requests
- manage workshop users
- edit workshop settings

Staff can:

- view dashboard
- view booking requests
- create booking requests from dashboard
- confirm booking requests
- reject booking requests
- cancel booking requests

Staff cannot:

- manage workshop users
- edit workshop settings

## Active Workshop Context

A logged-in user works within one active workshop at a time.

All dashboard data is shown for the active workshop.

All booking request queries are scoped by the active workshop.

All customer and vehicle queries are scoped by the active workshop.

Authorization checks must verify that the user has WorkshopUser membership for the active workshop.

User role is resolved from WorkshopUser for the active workshop.

## Booking Request

Belongs to one workshop.

Belongs to one customer.

May reference one vehicle.

Required fields:

- customer name
- customer phone
- problem description

Optional fields:

- preferred date
- vehicle information

Always stores snapshot fields:

- customer_name
- customer_phone

## Customer

Belongs to one workshop.

Identity inside a workshop:

- workshop_id + normalized phone

Can have many vehicles.

Store:

- name
- phone

## Vehicle

Belongs to one workshop.

Belongs to one customer in MVP.

Store:

- brand
- model
- license plate

## Booking Request Status

Booking request status is an enum/value object, not a domain entity.

- new
- confirmed
- rejected
- cancelled

## Dashboard

Owner and staff can see data for the active workshop:

- new requests
- confirmed requests
- cancelled requests

## Public Form

Customers can submit requests without authentication through a workshop-specific public form.

During public booking submission:

1. Find customer by `workshop_id` and normalized phone.
2. Create customer if missing.
3. Create booking request.
4. Link booking request to customer.
