# Business Rules

## Workshop

AutoService is a multi-workshop SaaS platform.

Workshop represents one auto workshop business.

Workshop has many workshop memberships, customers, vehicles, and booking requests.

## Workshop Membership

User is a platform account.

User does not belong directly to a workshop.

One user may belong to multiple workshops through WorkshopUser.

One workshop may have multiple users through WorkshopUser.

WorkshopUser has one workshop-specific role in MVP:

- owner
- staff

Authorization is evaluated in the context of the active workshop.

Workshop users can only access data from the active workshop where they have membership.

MVP roles use simple role checks only.

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

## Booking Request Creation

A booking request created from chat-first public intake must belong to the workshop from the public route.

Chat-first public intake:

- stores the original customer message
- creates `status = new`
- sets `workshop_id` from `/w/{workshop:slug}`
- leaves `customer_id` and `vehicle_id` empty until staff review or later enrichment
- may store a safely extracted phone number in `customer_phone`
- appears only in the matching active workshop dashboard

Workshop-scoped booking creation remains supported for existing workshop-specific flows and creates `status = new`.

A workshop-scoped booking request belongs to one workshop.

A workshop-scoped booking request belongs to one customer.

A booking request may reference one vehicle.

Required for workshop-specific booking:

- name
- phone
- problem description

During public booking submission:

1. Find customer by `workshop_id` and normalized phone.
2. Create customer if missing.
3. Create booking request.
4. Link booking request to customer.

Booking request always stores snapshot fields:

- customer_name
- customer_phone

Existing customer name must not be automatically overwritten by later public requests.

## Customer Identity

Customer identity inside a workshop is:

- workshop_id + normalized phone

Customer belongs to one workshop.

Customer can have many vehicles.

Vehicle belongs to one workshop.

Vehicle belongs to one customer in MVP.

## Initial Status

Chat-first public intake starts with:

new

Workshop-scoped booking requests start with:

new

Booking request status is an enum/value object, not a domain entity.

## Customer Contact

After a booking request is created, workshop staff contacts the customer manually.

## Request Processing

A request may be:

- confirmed
- rejected
- cancelled

## Multi-Branch Support

Multi-branch support is out of scope for MVP.

## Payments

Out of scope for MVP.

## Inventory

Out of scope for MVP.
