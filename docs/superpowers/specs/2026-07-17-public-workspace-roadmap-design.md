# Public Workspace Roadmap Design

## Purpose

Turn the public AutoService experience into a stable AI-chat-style workspace,
then add secure customer request history as a separate second milestone. The
milestones must be implemented and accepted in order. Request-history work must
not begin until the Welcome experience is complete and verified.

This design supersedes the page-layout and animation decisions in
`2026-07-17-collapsed-intake-starter-design.md`. It does not replace the
existing intake conversation rules, request payload, workshop selection, or
single-submit behavior.

## Delivery Strategy

The work is divided into two independently verifiable milestones:

1. **Welcome workspace**: build the complete public landing and intake shell.
2. **Customer request history**: add OTP-protected history and request details
   inside the same shell.

Each milestone has its own implementation tasks, focused tests, visual review,
and acceptance checkpoint. The second milestone depends on the first but must
not be mixed into the first milestone's implementation or acceptance criteria.

## Milestone 1: Welcome Workspace

### Goal

Replace the sparse centered input and abrupt full-page expansion with a useful
starting experience that feels like an AI chat application while preserving
the current public intake workflow.

### Desktop Layout

The Welcome page uses a stable two-column application shell:

- a persistent fixed-width navigation sidebar on the left; and
- a flexible central workspace for the starter and intake conversation.

The sidebar does not disappear or change width when the conversation starts.
It contains:

- AutoService brand;
- **New request** as the active primary action;
- **My requests** as a link to the existing OTP access flow;
- **How it works**, **Prices**, and **Help** as information-panel triggers;
- **For workshops** as the workshop-account entry point; and
- the existing staff-login entry in a secondary position.

The current wide public header must not duplicate navigation that has moved to
the sidebar. Essential public actions remain available in the shell.

### Mobile Layout

At the mobile breakpoint, the sidebar becomes an accessible menu opened from a
visible navigation button. The central workspace uses the available width, and
the active intake composer stays reachable above the mobile browser viewport.
Opening and closing the menu must not reset or submit the intake form.

### Initial Welcome Content

Before the first valid problem description is submitted, the central workspace
shows:

- the heading **What is happening with your car?**;
- a short explanation that the customer should describe the issue and the
  selected workshop will confirm the details;
- three concise example prompts for common situations; and
- the compact problem-description composer and Send action.

The initial example prompts are:

- **The car will not start**;
- **I hear an unusual noise**; and
- **I need scheduled maintenance**.

Selecting an example sets the problem-description field to that example and
focuses it. It does not submit or advance the conversation. The customer may
edit the resulting text before sending it.

Enter submits a valid first description and Shift+Enter inserts a newline.
Empty input remains on the Welcome state and displays the existing accessible
validation error.

### Conversation Transition

The starter and the expanded intake use the same form state and the same
composer ownership. A valid first submission:

1. preserves the submitted description;
2. fades and moves the introductory content out;
3. reveals the bounded transcript above the same composer; and
4. activates and focuses the phone step.

The transition lasts approximately 200-250 milliseconds. It uses opacity and a
small vertical movement rather than swapping to a different full-page card.
The central column width, sidebar width, and composer position remain visually
stable. Reduced-motion users receive an immediate state change without the
animated movement.

The transcript has a bounded height with internal scrolling. It must not grow
the document from top to bottom as more answers appear. The existing explicit
edit return-context behavior, optional-details behavior, workshop selection,
confirmation, cancellation, server validation, and final single POST remain
unchanged.

### Information Panels

**How it works**, **Prices**, and **Help** open lightweight overlay panels over
the workspace. Closing a panel returns the customer to the exact unfinished
intake state. These panels must not navigate away, recreate the form, or clear
typed text.

The Prices panel does not provide invented amounts, AI estimates, diagnoses, or
availability. It explains the process:

1. the customer describes the issue;
2. the workshop reviews or diagnoses it;
3. the workshop prepares or confirms the estimate; and
4. work proceeds only after the applicable customer confirmation.

### Milestone 1 Component Boundaries

- A public workspace shell owns desktop/mobile navigation and information-panel
  state.
- `Welcome.vue` composes the shell with the intake feature and owns only
  page-level presentation state.
- `PublicIntakeFlow.vue` remains the single owner of the Inertia form,
  conversation state, validation, and intake transitions.
- The transcript and focused step components retain their existing feature
  responsibilities.
- No global store, duplicate starter form, pricing service, or new persistence
  is introduced.

### Milestone 1 Acceptance Criteria

- The fresh Welcome page contains useful introductory content, prompt examples,
  a compact composer, and a full desktop sidebar.
- Example prompts fill and focus the field but never submit automatically.
- The first valid submission opens the bounded conversation smoothly above the
  same composer without a full-page flash or width change.
- The sidebar remains stable throughout the intake.
- Information panels preserve all unfinished intake state.
- Mobile navigation is keyboard accessible and does not obstruct the active
  composer.
- Existing intake validation, editing, cancellation, workshop choice, and
  single-submit behavior remain correct.
- Focused frontend tests, lint/format checks, and a desktop/mobile visual review
  pass before Milestone 2 begins.

## Milestone 2: OTP-Protected Request History

### Goal

After the Welcome workspace is accepted, make **My requests** behave like a
chat-history list while keeping customer access protected by the existing
phone OTP session.

### Access States

Before phone verification, the sidebar shows a single **My requests** entry.
Selecting it opens the existing customer-portal access and verification flow.
No request title, count, workshop, status, or other history metadata is shown
before successful verification.

The access and code-verification screens render in the central area of the
shared workspace during this milestone. The sidebar remains visible on desktop
and available through the mobile menu.

After verification, the customer portal renders inside the public workspace
shell and the sidebar replaces the single entry with recent request history.
The verified phone and expiration time remain server-side session state. The
frontend must not decide which phone owns a request.

### Sidebar History

The sidebar shows at most the 10 newest requests, ordered newest first and
grouped for presentation as:

- Today;
- Last 7 days, excluding Today; and
- Earlier.

Each compact history item displays:

- a one-line title derived from the problem description, with a neutral
  fallback when no suitable description exists;
- the current request status;
- the workshop name; and
- a concise submitted date.

Long values are truncated in the sidebar and remain available in the request
detail. A **Show all requests** action appears when more than 10 requests exist
and opens the complete request list in the central workspace. **New request**
remains the first primary action and returns to a clean Welcome intake.

### Request Detail

Selecting a history item opens a read-only request detail in the central
workspace while leaving the sidebar visible. The detail contains only data the
customer originally submitted or safe workshop/request metadata:

- full problem description;
- current status;
- submitted date;
- last-updated date;
- workshop identity;
- vehicle snapshot when present; and
- optional customer-provided details when present.

The current data model does not persist a status event log. The first history
iteration therefore shows **Request submitted** and **Current status / last
updated**, not a fabricated full timeline. A true event timeline is a separate
future feature requiring persisted status events.

The detail does not display a message composer and does not imply live chat
with the workshop. Customer cancellation, replies, documents, estimates, and
repair-order details are outside this milestone unless separately designed.

### Backend Responsibilities and Security

The customer-portal controller remains responsible only for HTTP orchestration.
A customer-portal query resolves list and detail data using the normalized phone
stored in the verified OTP session.

The list query:

- filters booking requests by the exact normalized verified phone;
- eager-loads only presentation data required from the workshop;
- orders requests newest first; and
- maps models to a minimal customer-safe payload.

The detail query receives the route identifier but must scope the lookup by the
same verified normalized phone. A missing or non-owned request returns 404 so
the response does not reveal whether another customer's request exists.

An expired or invalid verification session returns the customer to the access
flow using the existing middleware behavior. No customer login user is created,
and no direct `user.workshop_id` or unscoped booking-request lookup is added.

### Milestone 2 Empty and Error States

- A verified phone with no requests sees an empty state and **Create a new
  request** action.
- An expired verification session returns to phone verification with the
  existing expiration message.
- A request outside the verified phone scope returns 404.
- Missing optional vehicle or customer details are omitted rather than shown
  as empty rows.
- Loading or navigation keeps the shared shell dimensions stable.

### Milestone 2 Acceptance Criteria

- No history metadata is available before OTP verification.
- A verified customer sees only booking requests matching the verified
  normalized phone, including requests submitted to different workshops.
- Recent requests appear in the persistent sidebar with clear grouping and
  status treatment.
- Selecting a request opens customer-safe read-only details in the center.
- Direct access to another phone's request returns 404.
- Empty and expired-session states guide the customer back to a safe next step.
- Backend feature tests cover the happy path, exact phone scoping, unauthorized
  detail access, empty history, and expired sessions.
- Frontend tests cover sidebar history, grouping, truncation, selection, detail
  presentation, and responsive navigation.
- Focused backend and frontend validation plus a desktop/mobile visual review
  pass before the milestone is accepted.

## Explicitly Deferred Work

- Full persisted request-status event history;
- live customer-to-workshop messaging;
- customer cancellation or editing after submission;
- customer access to estimates, documents, or internal repair orders;
- AI diagnosis, AI pricing, promises, or availability;
- unfinished intake persistence across devices or browser sessions; and
- unrelated admin or workshop-dashboard redesign.

## Implementation Handoff

The implementation plan derived from this design must keep the milestone gate
explicit. It must finish, test, and visually accept Milestone 1 before listing
Milestone 2 tasks as executable. A new session should read this design, the
implementation plan, the repository `AGENTS.md`, and the architecture rules
before changing code.
