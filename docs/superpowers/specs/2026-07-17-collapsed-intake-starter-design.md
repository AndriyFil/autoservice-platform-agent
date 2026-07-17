# Collapsed Intake Starter Design

## Goal

Show a compact, centered problem-description composer when the public homepage
first opens, then expand the existing bounded intake chat only after the
customer submits a valid first description.

## Scope

This change affects only the initial presentation and expansion behavior of the
public homepage intake. It does not change routes, request payloads, database
schema, booking-request creation, workshop rules, customer portal behavior, or
the expanded chat's existing interaction model.

## Initial Experience

Before the conversation starts, the homepage displays the public header and a
centered compact composer. The full transcript card and trust panel are hidden.
The composer contains:

- the existing problem-description field and form value;
- a visible send action;
- the existing 5,000-character limit and accessible label;
- the honeypot field required by the final request payload; and
- a field-specific validation message when the description is empty.

The compact composer uses AI-chat keyboard behavior: Enter submits the first
description, while Shift+Enter inserts a newline. Typing or focusing the field
does not expand the chat.

## Expansion and Conversation Flow

Submitting an empty description keeps the homepage collapsed and shows the
existing problem validation error. Submitting a valid description advances the
existing conversation state from `problem` to `phone`. That state transition
expands the same intake component; it does not create a new form or transfer
data between components.

After expansion:

- the bounded transcript and docked composer use the existing repaired chat
  design;
- the initial problem prompt and submitted description appear in history;
- the phone question and phone composer become active;
- focus moves to the phone input; and
- the transcript scrolls internally to the newest exchange without moving the
  browser page.

The trust panel appears only after expansion, beside the chat at the existing
desktop breakpoint. On smaller screens it follows the existing responsive
layout.

## State and Component Boundaries

`PublicIntakeFlow.vue` remains the single owner of the Inertia form and
conversation state. Its render mode is derived from durable state:

- collapsed when `conversationState === 'problem'` and no edit is active;
- expanded after durable progress moves beyond `problem`; and
- expanded while editing any answer, including the problem description from a
  later state.

The flow emits `expanded-change` with a boolean whenever its render mode
changes. `Welcome.vue` owns only page layout: it centers the collapsed flow or
renders the existing chat-and-trust-panel grid when expanded. No global store
or duplicate starter form is introduced.

The compact starter is kept within `PublicIntakeFlow.vue` because it edits the
same form field and invokes the same problem transition. The expanded
transcript remains in `PublicIntakeTranscript.vue`.

## Editing, Reset, and Error Behavior

- Editing the problem after the phone step or from confirmation stays expanded
  because durable conversation progress is not reset.
- Saving or cancelling an edit follows the existing explicit edit return
  context and never collapses the chat.
- A confirmed cancellation resets the unsent form, returns durable state to
  `problem`, emits `expanded-change: false`, and restores the centered starter.
- Server validation opened from confirmation remains expanded and focuses the
  matching editor.
- The final request is still posted once, only from confirmation. The compact
  starter never posts to the backend.

## Accessibility and Motion

- The compact textarea has an accessible label, error relationship, visible
  focus state, and keyboard-operable send button.
- Enter submits only without Shift; Shift+Enter preserves multiline input.
- Expansion announces the phone step through the existing live region and
  moves focus to the phone input.
- Existing reduced-motion handling remains in effect for transcript scrolling.
- No hidden expanded transcript remains focusable while the starter is
  collapsed; the two modes are conditionally rendered.

## Testing Strategy

Rendered Vue tests cover:

1. The initial state shows the compact starter and omits the transcript.
2. Empty submission remains collapsed and shows the problem error.
3. Focusing and typing do not expand the chat.
4. Enter with a valid description expands the chat and activates phone.
5. Shift+Enter does not expand and preserves multiline input.
6. Clicking Send performs the same expansion as Enter.
7. The submitted description appears in expanded history and no future
   questions appear.
8. Editing the description after expansion remains expanded and returns to the
   correct active step.
9. Confirmed cancellation resets to the compact starter.
10. `Welcome.vue` hides the trust panel while collapsed and displays it after
    `expanded-change: true`.

Existing state, submission, validation, workshop, header-navigation, and public
experience tests remain green. No backend test changes are required because
the HTTP contract does not change.

## Acceptance Criteria

- A fresh homepage shows only the compact centered description composer, not a
  full empty chat card.
- The trust panel is hidden until the conversation begins.
- Focus and typing alone do not expand the chat.
- A valid first submission expands the chat and opens the phone step.
- Enter submits, Shift+Enter adds a newline, and the send button is equivalent
  to Enter.
- Empty input remains collapsed with an accessible error.
- The first description is preserved in chat history.
- Later edits never collapse or reveal unanswered exchanges.
- Confirmed cancellation returns to the compact starter.
- The expanded flow retains the bounded transcript, docked composer, optional
  detail, workshop, validation, and single-submit behavior already implemented.

## Out of Scope

- AI-generated replies, diagnosis, pricing, or availability;
- persistence of an unfinished draft across reloads;
- animation beyond existing focus and reduced-motion-safe transcript scrolling;
- changes to trust-panel copy or expanded-chat visual styling;
- changes to backend validation, routes, payloads, or persistence.
