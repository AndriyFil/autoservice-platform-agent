# Customer Portal Repair Order Status Design

## Goal

Let a verified customer see when a repair order has been created from their service request and follow its current operational status from the existing request-detail page.

## Scope

When the booking request has a linked repair order, the customer request detail will show:

- repair order identifier;
- current repair order status label;
- opened date and time;
- last updated date and time;
- a repair-order milestone in the existing request progress timeline.

When no repair order is linked, the page remains unchanged.

The customer portal will not expose estimates, prices, line items, documents, internal notes, staff identity, or workshop-only actions in this change.

## Data and Access

`CustomerRequestShowQuery` remains the security boundary. It first scopes the booking request to the phone verified in the active customer-portal session, then eager-loads the linked repair order. Only the approved customer-safe fields are mapped into the Inertia payload.

The payload adds a nullable `repairOrder` object:

```text
repairOrder: {
  id: number
  status: { value: string, label: string }
  openedAt: ISO-8601 string
  updatedAt: ISO-8601 string
} | null
```

No direct customer route to the internal repair-order dashboard is introduced.

## Interface

`CustomerRequestDetail` renders a distinct “Repair order” section when `repairOrder` is present. It shows the order number, status, opened time, and last updated time using the existing public card visual language.

The “Request progress” timeline gains a final “Repair order created” milestone containing the current order status and its last-updated timestamp. The existing booking-request status remains visible so the customer can distinguish intake state from workshop work state.

## Testing

- Feature test: an owned request with a linked repair order receives only the approved repair-order fields.
- Feature test: another verified phone cannot access the request or its linked repair order.
- Component test: linked repair-order details and timeline milestone render.
- Component test: no repair-order block renders when the value is `null`.
