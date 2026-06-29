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

May be unassigned during chat-first intake.

Belongs to one workshop after routing or workshop-specific creation.

May have no customer during initial chat-first intake.

Belongs to one customer after customer resolution.

May reference one vehicle.

Required fields for workshop-specific booking:

- customer name
- customer phone
- problem description

Required fields for chat-first landing intake:

- original message
- problem description copied from the original message

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

## Repair Order

RepairOrder is the staff-owned work record after intake review.

A repair order:

- belongs to one workshop
- is created by workshop owner or staff
- may be created from a reviewed booking request
- may belong to a customer
- may reference a vehicle
- may contain staff-authored estimate lines

A repair order is not:

- the intake aggregate
- an AI diagnosis
- a customer approval portal
- an invoice
- a payment record
- an accounting record

## Estimate Lines

Estimate lines belong to a repair order.

Supported foundation line types:

- labor
- part
- fee
- discount

Workshop staff author estimate lines manually.

The system may calculate totals from line values, but must not use AI to diagnose issues, recommend repairs, or estimate prices.

Money is stored as integer cents.

Invoices come later, after estimate approval and/or work completion. Payment, PDF export, customer approval portal, inventory management, and accounting are out of scope.

## Booking Request Status

Booking request status is an enum/value object, not a domain entity.

- submitted
- new
- confirmed
- rejected
- cancelled

## Dashboard

Owner and staff can see data for the active workshop:

- new requests
- confirmed requests
- cancelled requests
- repair order backend foundation
- repair order estimate totals from staff-entered lines

## Public Intake

MVP currently has two public intake paths:

- Chat-first landing intake creates an unassigned `BookingRequest` with `status = submitted` and `workshop_id = null`.
- The older workshop-specific public booking form creates a workshop-scoped `BookingRequest` with `status = new`.

The MVP direction is the chat-first landing intake plus central admin assignment.

Public landing intake must not ask the customer to select a workshop. Workshop routing happens internally through the central admin queue.

During workshop-specific public booking submission:

1. Find customer by `workshop_id` and normalized phone.
2. Create customer if missing.
3. Create booking request.
4. Link booking request to customer.
