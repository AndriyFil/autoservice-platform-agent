# Domain Model

## Source Documents

- `docs/product/vision.md`
- `docs/product/mvp-scope.md`
- `docs/product/business-rules.md`

## MVP Domain Entities

### Workshop

Purpose:

- Represents one auto workshop business in the SaaS platform.

Relationships:

- Has many workshop memberships.
- Has many customers.
- Has many vehicles.
- Has many booking requests.

MVP: yes

### User

Purpose:

- Represents a platform account.

Relationships:

- Does not belong directly to a workshop.
- May belong to multiple workshops through WorkshopUser.

MVP: yes

### WorkshopUser

Purpose:

- Represents membership between a user and a workshop.
- Stores the user's role for that workshop.

Relationships:

- Belongs to one user.
- Belongs to one workshop.
- Has one workshop-specific role in MVP: `owner` or `staff`.
- Authorization is evaluated in the context of the active workshop.

MVP: yes

### Customer

Purpose:

- Represents the person requesting vehicle maintenance, diagnostics, or repairs.

Relationships:

- Belongs to one workshop.
- Has name and phone.
- Has booking requests.
- Has many vehicles.

Identity:

- `workshop_id` + normalized phone.

MVP: yes

### Vehicle

Purpose:

- Stores basic vehicle information related to a customer request.

Relationships:

- Belongs to one workshop.
- Belongs to one customer in MVP.
- May be referenced by booking requests.

MVP: yes

### BookingRequest

Purpose:

- Captures a customer's request for workshop help.

Relationships:

- Belongs to one workshop.
- Public chat-first intake resolves the workshop from `/w/{workshop:slug}`.
- May have no customer during initial chat-first intake.
- Belongs to one customer after customer resolution.
- May reference one vehicle.
- Created by a public customer through chat-first landing intake or the older workshop-specific public form.
- Contains original customer message for chat-first intake.
- Contains customer name, customer phone, and required problem description after workshop-specific creation or later enrichment.
- Always stores snapshot fields: `customer_name`, `customer_phone`.
- May include preferred date.
- May include vehicle information.
- Has booking request status.
- Is reviewed by workshop staff.
- Leads to manual customer contact by workshop staff.
- Is the intake aggregate and may lead to one repair order after staff review.
- Must not contain staff-authored estimates, invoices, payments, accounting records, or repair execution details.

MVP: yes

### RepairOrder

Purpose:

- Represents staff-owned workshop work after intake review.
- Tracks workshop work that may start from a reviewed booking request.
- Holds staff notes and safe estimate lines before any future invoice workflow exists.

Relationships:

- Belongs to one workshop.
- May originate from one booking request.
- Links to the source booking request when created from intake review.
- Copies the booking request problem description when created from a booking request.
- May belong to one customer.
- May reference one vehicle.
- Has many repair order lines.
- May record the staff user who created it.
- Represents operational workshop order state only.
- May have staff-authored estimate lines and generated estimate snapshots.
- Does not represent customer approval, invoice, payment, PDF export, or accounting.

MVP: yes

### RepairOrderLine

Purpose:

- Represents one staff-authored estimate line on a repair order.

Allowed line types:

- labor
- part
- fee
- discount

Rules:

- Lines are created or edited by workshop staff.
- Money is stored as integer cents.
- The system may calculate totals from staff-entered values.
- Discount lines reduce repair order totals.
- A draft or in-progress repair order can generate an estimate snapshot when it has at least one line.
- AI must not diagnose repairs, recommend work, or generate prices.
- Repair order lines are not invoices and do not record payment.
- Invoice generation comes later, after approval or completion rules exist.

MVP: yes

## Enums / Value Objects

### BookingRequestStatus

Purpose:

- Tracks processing state for a booking request.

Relationships:

- Used by booking request.
- Initial chat-first workshop intake status is `new`.
- Initial workshop-scoped booking status is `new`.

MVP statuses:

- `new`
- `confirmed`
- `rejected`
- `cancelled`

MVP: yes

Domain entity: no

### RepairOrderStatus

Purpose:

- Tracks staff-owned operational repair order workflow state.
- Estimate lifecycle belongs to the Estimate domain, not RepairOrderStatus.
- Operators can start work directly from draft.

MVP statuses:

- `draft`
- `in_progress`
- `completed`
- `cancelled`

MVP: yes

Domain entity: no

### RepairOrderLineType

Purpose:

- Classifies staff-authored repair order estimate lines.

MVP values:

- `labor`
- `part`
- `fee`
- `discount`

MVP: yes

Domain entity: no

## Actors

Actors are not necessarily database tables. They describe who interacts with the system.

Workshop Owner and Workshop Staff are actors backed by User account plus WorkshopUser role.

In MVP, WorkshopUser has one role: `owner` or `staff`. Advanced permissions are not defined.

### Public Customer

Purpose:

- Creates a booking request through the public form without authentication.
- Public Customer is an actor, not a database table.

Relationships:

- Creates a booking request.
- Uses a workshop-specific public form.
- Provides customer contact information.
- Is linked to a Customer record during public booking submission.

MVP: yes

### Workshop Owner

Purpose:

- Reviews dashboard information for workshop requests.
- Manages workshop users.
- Edits workshop settings.

Relationships:

- Backed by WorkshopUser role `owner`.
- User has membership in the active workshop.
- Can view dashboard data for the active workshop.
- Can view, create, confirm, reject, and cancel booking requests.
- Can only access data from the active workshop.

MVP: yes

### Workshop Staff

Purpose:

- Views dashboard information for workshop requests.
- Manually contacts customers after booking requests are created.

Relationships:

- Backed by WorkshopUser role `staff`.
- User has membership in the active workshop.
- Can view dashboard data for the active workshop.
- Can view, create, confirm, reject, and cancel booking requests.
- Cannot manage workshop users.
- Cannot edit workshop settings.
- Contacts customers about booking requests.
- Can only access data from the active workshop.

MVP: yes

## Read Models / UI Views

### Dashboard

Purpose:

- Shows active workshop request lists by status.

Relationships:

- Shows booking requests with `new`, `confirmed`, `rejected`, and `cancelled` statuses.

MVP: yes

## Future Concepts

### Service Work

Purpose:

- Use `RepairOrder` for the MVP staff-owned work record.

Relationships:

- Do not introduce a separate ServiceWork entity unless a future scheduling or execution workflow creates a clear responsibility that RepairOrder should not own.

MVP: no

### Service History

Purpose:

- Tracks past customer or vehicle service records.

Relationships:

- Related to customers and vehicles.
- Required service history details are unresolved. See `docs/product/open-questions.md`.

MVP: no

### Inventory

Purpose:

- Tracks workshop parts or stock.

Relationships:

- Out of scope for MVP.

MVP: no

### Invoice

Purpose:

- Represents billing after estimate approval and/or work completion.

Relationships:

- May be created later from an approved or completed repair order.
- Not part of the Repair Order + Estimate foundation milestone.
- Does not exist only because an estimate exists.

MVP: no

### Payment

Purpose:

- Represents customer payment processing or recording.

Relationships:

- Out of scope for MVP.

MVP: no

### Accounting

Purpose:

- Represents financial accounting features.

Relationships:

- Out of scope for MVP.

MVP: no

### Branch

Purpose:

- Represents multiple workshop locations.

Relationships:

- Out of scope for MVP.

MVP: no

### Schedule

Purpose:

- Represents advanced scheduling.

Relationships:

- Out of scope for MVP.
- Preferred date may be provided on a booking request.
- Whether preferred date creates any scheduling entity is unresolved. See `docs/product/open-questions.md`.

MVP: no
