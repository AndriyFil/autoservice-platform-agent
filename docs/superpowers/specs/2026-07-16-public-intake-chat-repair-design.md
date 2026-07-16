# Public Intake Chat Repair Design

## Goal

Repair the global public intake so it behaves like a bounded AI-style chat,
preserves the customer's real progress during edits, and resolves the review
findings in the newly implemented homepage flow.

## Scope

This change covers only the public homepage intake and its shared public
header. It does not change routes, database schema, workshop selection rules,
booking-request creation, customer portal behavior, staff authentication, or
dashboard design.

The repair includes:

- a fixed-height chat viewport with internal transcript scrolling;
- a response composer anchored to the bottom of the chat card;
- correct return behavior after editing an earlier answer;
- no rendering of unanswered or empty future exchanges;
- support for entering both optional name and vehicle details;
- field-specific optional-detail validation feedback;
- normal browser navigation for cross-origin admin links;
- rendered interaction tests for the intake component.

## Confirmed Failure Cause

The current edit flow uses `editingState !== null` as a signal to return to
`confirmation`. If the customer edits the description while still answering
the phone question, saving the description incorrectly moves the conversation
to confirmation. `stateRank` then becomes the confirmation rank, which renders
all later prompts and empty answer bubbles at once.

The layout compounds the bug because the transcript is an expanding document.
Every newly visible exchange increases the page height and pushes the active
control downward, rather than keeping the current exchange at the bottom of a
chat viewport.

## Interaction Design

### Chat shell

The public intake card contains two vertically arranged regions:

1. A transcript region that uses the available card height and scrolls
   internally.
2. A composer region that remains visible at the bottom of the card and renders
   only the control for the active question or edit.

On desktop, the card uses a bounded height derived from the viewport and keeps
the trust panel outside the chat. On mobile, the chat uses the available
dynamic viewport height so the composer remains reachable when the software
keyboard is open. The transcript uses bottom alignment while its content is
short and normal scrolling after its content exceeds the available height.

After every successful advance, edit-open, edit-save, validation redirect, or
reset, the transcript scrolls to its newest relevant exchange. The browser page
must not jump merely because a new chat message appears. Motion respects
`prefers-reduced-motion` by switching smooth scrolling to immediate scrolling.

### Visible conversation

Transcript visibility is derived from completed answers, not from the numeric
rank of the active editor. The UI renders:

- the initial problem question;
- each non-empty completed customer answer;
- the current active question;
- the final confirmation only after a valid workshop has been selected.

The UI never renders blank phone, optional-detail, workshop, or confirmation
bubbles. Editing one answer does not reveal unanswered future questions and
does not erase or duplicate completed history.

### Editing

Editing uses an explicit context containing:

- the answer being edited; and
- the conversation state to return to after saving or cancelling the edit.

Opening an edit captures the current active state. Saving a valid edit returns
to that captured state. Therefore:

- editing the problem while the phone question is active returns to `phone`;
- editing any answer from final confirmation returns to `confirmation`;
- editing the workshop from confirmation returns to confirmation after a valid
  workshop is selected;
- an invalid edit remains in its editor and shows its error.

The implementation must not use `editingState !== null` as a shortcut for
returning to confirmation.

### Optional details

After the phone answer, the optional-details composer offers name and vehicle
editors plus a Continue action. Saving either editor returns to the
optional-details composer, where the completed item is visibly marked and the
customer may add or edit the other item. Continue advances to workshop
selection. Both fields remain optional, and the customer may continue without
either.

At confirmation, existing name and vehicle answers each have their own edit
action. A missing optional answer is not represented by a fake customer bubble.

### Validation

Frontend constraints mirror the FormRequest limits for message, phone, customer
name, vehicle text fields, and vehicle year. Server validation remains the
source of truth.

Each optional field renders its exact server error and exposes the error through
`aria-invalid` and `aria-describedby`. Errors are cleared only when the
corresponding value changes or when that field passes local validation. Saving
an optional editor must not clear unresolved errors or advance to another
state.

## State and Component Boundaries

`state.ts` remains the pure transition layer. It will expose transition results
that identify the next state without knowing about DOM focus or scrolling. The
edit return target will be explicit rather than inferred from a boolean.

`PublicIntakeFlow.vue` remains the owner of the single Inertia form and final
POST. Its responsibilities are state orchestration, composer selection, focus,
and scroll behavior. The transcript markup is extracted to a presentational
`PublicIntakeTranscript.vue` that receives completed values and emits edit
requests. The active composers remain in `PublicIntakeFlow.vue` so form
mutation and validation are not distributed across components. No store or new
application architecture is introduced.

`PublicHeader.vue` continues to use Inertia `Link` for same-origin public
destinations. Staff login and workshop registration use normal `<a>` elements
because their URLs may point to a different origin in split-host deployments.

## Error and Empty States

- No workshops: the workshop composer shows the existing unavailable message
  and does not expose confirmation or submission actions.
- Server validation: the flow opens the matching editor, preserves all entered
  values, announces the error, focuses the invalid field, and keeps the
  composer visible.
- Submission processing: Send request is disabled and cannot issue duplicate
  POSTs.
- Cancellation: the existing confirmation remains in the composer area and a
  confirmed cancellation resets only the unsent local form.

## Testing Strategy

Pure state tests cover:

- normal progression;
- editing from an intermediate state and returning to that state;
- editing from confirmation and returning to confirmation;
- optional name then vehicle and vehicle then name;
- invalid edits remaining in their editor;
- no submission outside confirmation.

Rendered component tests cover the reported regression exactly:

1. Enter a description and advance to phone.
2. Edit the description.
3. Save the edit.
4. Assert that phone remains active and that optional, workshop, and
   confirmation exchanges are absent.

Rendered tests also cover internal transcript scrolling hooks, anchored
composer presence, both optional details, field-specific errors, cancellation,
workshop selection, and one final POST. Cross-domain header tests assert normal
anchors for admin URLs and Inertia links for same-origin customer navigation.

Adding a Vue component test utility or DOM test environment requires explicit
dependency approval before installation. Existing source-string visual
contract checks may remain only for stable design tokens; they are not accepted
as behavioral coverage.

Focused Laravel feature tests continue to cover workshop resolution,
workshop-scoped request creation, validation, throttling, and split-host route
behavior. No migration or full backend-suite run is required for this repair.

## Acceptance Criteria

- The initial homepage retains the approved visual design.
- The active exchange stays at the bottom of the chat card.
- Conversation growth scrolls inside the card instead of lengthening the page.
- Editing the description from the phone step returns to the phone step.
- Saving an edit never reveals unanswered future exchanges.
- Editing from confirmation returns to confirmation.
- Customers can provide both name and vehicle details in either order.
- Exact optional-field errors are visible and accessible.
- Split-host staff login and registration perform normal browser navigation.
- The final request is posted exactly once and only from confirmation.
- Focused frontend and Laravel regression tests pass.

## Out of Scope

- AI-generated responses, diagnosis, pricing, or availability promises;
- persistence of unfinished drafts across reloads;
- redesigning customer portal, authentication, dashboard, or trust-panel copy;
- changing booking-request payloads, routes, domain rules, or database schema;
- adding a global frontend store.
