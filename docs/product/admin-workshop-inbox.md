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

Public chat-first intake creates a `BookingRequest` with:

- `status = submitted`
- `original_message` preserved
- `problem_description` copied from the customer message
- `workshop_id = null`
- `customer_id = null`
- `vehicle_id = null`

The existing dashboard booking request list is scoped to the active workshop through `WorkshopUser` and only shows requests already assigned to that workshop. Because public chat-first requests are currently unassigned, they should not be added to the workshop dashboard until the product and architecture rules for visibility and assignment are explicit.

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

For MVP, use a central admin queue.

Public chat-first intake requests are not visible in any workshop dashboard while `workshop_id = null`.

A request becomes workshop-scoped only when an authorized central admin assigns it to a workshop. After assignment, the request appears in that workshop's regular dashboard and is processed by workshop owner or staff using the normal active-workshop rules.

Rejected alternatives for MVP:

- Workshop claim queue: deferred until explicit cross-workshop visibility and claim rules exist.
- Public workshop selection on the landing page: rejected because it exposes routing complexity to customers and weakens the chat-first intake direction.
- Automatic routing: deferred until a safe routing signal and review model are defined.

Do not reintroduce public workshop selection on the landing page as part of this decision.

## Implemented Foundation

The current implementation supports the safe foundation for chat-first intake:

- The public landing page accepts one natural-language customer message.
- Submitting the landing intake creates a `BookingRequest` with `status = submitted`.
- The intake request stores the customer's original wording in `original_message`.
- `problem_description` is copied from the customer message for compatibility with existing request views.
- A safely extracted phone number may be stored in `customer_phone`.
- `workshop_id`, `customer_id`, `vehicle_id`, and `created_by_user_id` remain `null`.
- Intake extraction is behind an `IntakeExtractorInterface` boundary.
- No real OpenAI provider call is configured.
- The workshop dashboard remains scoped by active `WorkshopUser` membership and only shows workshop-assigned requests.
- A backend-only central admin query foundation exists for unassigned submitted intake requests.
- No central admin route, UI, or assignment action exists yet because platform-admin authorization is not modeled.

The older workshop-specific public booking form still exists separately. It creates workshop-scoped requests with `status = new` and should not be confused with the landing-page chat-first intake flow.

## Status Language

Internal statuses may include `submitted`, `new`, `confirmed`, `rejected`, and `cancelled`.

Staff-facing labels should stay operational and avoid promises:

- `submitted`: Needs review
- `new`: Ready to contact
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
- Unassigned `submitted` request visibility has an explicit product rule.
- Frontend status types include `submitted` where the backend can return it.
- Staff UI shows original customer wording before extracted details.
- Staff UI never presents LLM output as diagnosis, pricing, repair advice, or availability.

## Central Admin Queue MVP Scope

The MVP central admin queue should include only:

- a list of unassigned `submitted` requests
- request received time
- original customer message
- safe extracted intake hints, if available
- missing required information, if known
- assignment to one selected workshop
- rejection or cancellation when the request should not enter a workshop workflow

Assignment should be one explicit backend use case.

Recommended assignment result:

- set `workshop_id` to the selected workshop
- change status from `submitted` to `new`
- preserve `original_message`
- preserve `problem_description`
- create or link customer and vehicle records only when the assignment workflow has explicit rules for doing so

Do not let assignment imply appointment confirmation, diagnosis, price, availability, or repair commitment.

## Safety Boundaries

Central admin queue safety rules:

- Unassigned `submitted` requests must not appear in regular workshop dashboards.
- Workshop owner and staff roles must not see global unassigned requests unless a future permission model explicitly allows it.
- Workshop staff must not claim global requests in MVP.
- Public customers must not choose a workshop from the landing-page chat-first intake.
- The system must not automatically route requests to a workshop without an approved routing rule.
- The central queue must show the original customer message before any extracted or summarized fields.
- Extracted intake data must be treated as assistance, not truth.
- Staff confirmation remains the final authority for visit time, service expectations, and follow-up.

## Future Work

Future implementation work:

- define the central admin role or permission model
- expose the backend central admin queue through an authorized route
- build an assignment action for routing one submitted request to one workshop
- decide whether assignment creates or links `Customer` and `Vehicle` records immediately or defers that to workshop staff
- add staff-facing history for assignment, rejection, cancellation, and contact attempts
- update dashboard frontend status types to include `submitted` only where a view can actually receive that status
- add safe extraction display once extraction fields beyond phone are persisted
- add tests proving unassigned requests do not leak into workshop-scoped dashboards
