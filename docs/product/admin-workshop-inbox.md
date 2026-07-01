# Admin / Workshop Inbox Planning Note

## Goal

Define the safe staff-facing inbox direction for chat-first intake before changing dashboard UI or request routing.

The inbox should help workshop staff answer:

- Who should we contact?
- What did the customer actually say?
- What safe intake details are known?
- What is still missing?
- What is the next staff-owned action?

## Current State

Public chat-first intake is tenant-scoped. Each workshop has its own public page:

```txt
GET  /w/{workshop:slug}
POST /w/{workshop:slug}/intake
```

Submitting that page creates a `BookingRequest` with:

- `status = new`
- `original_message` preserved
- `problem_description` copied from the customer message
- `workshop_id` set to the route workshop
- `customer_id = null`
- `vehicle_id = null`

The dashboard booking request list is scoped to the active workshop through `WorkshopUser` and shows new requests for that active workshop only.

## Safe Inbox Direction

The staff inbox should show request records in a way that supports triage, not automated decisions.

List view should prioritize:

- received time
- current staff-facing status
- original message or safe customer summary
- contact state
- vehicle state
- preferred time state
- clear view/action entry point

Detail view should prioritize:

- original customer message first
- safe extracted intake details second
- missing required field, if any
- timeline and staff status history
- staff actions such as assign, call, confirm, reject, or cancel only after those actions have approved backend rules

## MVP Routing Decision

For MVP, do not use a central admin queue for public intake routing.

The workshop context comes from the public URL. Public intake must never silently create unassigned `BookingRequest` records.

After submission, the request appears in the route workshop's regular dashboard and is processed by workshop owner or staff using the normal active-workshop rules.

Rejected alternatives:

- Central admin assignment queue: rejected for public intake routing.
- Workshop claim queue: deferred until explicit cross-workshop visibility and claim rules exist.
- Public workshop selection on the landing page: rejected because it exposes routing complexity to customers and weakens the chat-first intake direction.
- Default or first-workshop fallback: rejected because it can leak customer requests into the wrong tenant.
- Automatic routing without URL workshop context: rejected until a safe routing signal and review model are defined.

Do not reintroduce public workshop selection on the landing page as part of this decision.

## Implemented Foundation

The current implementation supports the safe foundation for chat-first intake:

- The public landing page accepts one natural-language customer message.
- The workshop public intake page accepts one natural-language customer message.
- Submitting workshop public intake creates a `BookingRequest` with `status = new`.
- The intake request stores the customer's original wording in `original_message`.
- `problem_description` is copied from the customer message for compatibility with existing request views.
- A safely extracted phone number may be stored in `customer_phone`.
- `workshop_id` is set from the route workshop.
- `customer_id`, `vehicle_id`, and `created_by_user_id` remain `null`.
- Intake extraction is behind an `IntakeExtractorInterface` boundary.
- No real OpenAI provider call is configured.
- The workshop dashboard remains scoped by active `WorkshopUser` membership and only shows requests for the active workshop.
- There is no central admin route, UI, query, or assignment action for public intake routing.

The older workshop-specific public booking form still exists separately. It also creates workshop-scoped requests with `status = new` and should not be confused with the chat-first intake flow.

## Status Language

Internal statuses may include `new`, `confirmed`, `rejected`, and `cancelled`.

Staff-facing labels should stay operational and avoid promises:

- `new`: Needs review
- `confirmed`: Confirmed by staff
- `rejected`: Rejected
- `cancelled`: Cancelled

Confirmation must mean staff confirmation only. It must not imply automatic appointment availability, price approval, diagnosis, or repair commitment.

## Explicitly Out Of Scope

Do not build:

- real OpenAI API integration
- AI diagnosis
- repair recommendations
- price estimates
- appointment availability promises
- automatic appointment confirmation
- customer portal
- public workshop selection on the landing page
- `Chat`, `Conversation`, `AiThread`, workflow engine, or queue-first architecture

## Future Acceptance Criteria

When inbox implementation is approved later:

- Dashboard queries remain scoped by active `WorkshopUser` membership.
- New request visibility remains tenant-scoped by active workshop.
- Frontend status types include only statuses the backend can return.
- Staff UI shows original customer wording before extracted details.
- Staff UI never presents LLM output as diagnosis, pricing, repair advice, or availability.

## Safety Boundaries

Inbox safety rules:

- Public intake must require a route workshop.
- Workshop owner and staff roles must not see requests from other workshops.
- Workshop staff must not claim global requests in MVP.
- Public customers must not choose a workshop from the landing-page chat-first intake.
- The system must not use a default workshop fallback.
- The inbox must show the original customer message before any extracted or summarized fields.
- Extracted intake data must be treated as assistance, not truth.
- Staff confirmation remains the final authority for visit time, service expectations, and follow-up.

## Future Work

Future implementation work:

- decide whether public intake creates or links `Customer` and `Vehicle` records immediately or defers that to workshop staff
- add staff-facing history for rejection, cancellation, confirmation, and contact attempts
- add safe extraction display once extraction fields beyond phone are persisted
- keep tests proving one workshop cannot see another workshop's new requests
