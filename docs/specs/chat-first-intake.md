# Chat-First Intake Specification

## Goal

Define the approved product and implementation boundaries for AutoService chat-first customer intake.

AutoService is a chat-first auto service intake and workshop management platform. It is not an AI mechanic. It is an AI intake assistant.

## Allowed LLM Responsibilities

LLM may be used only to support intake data collection:

- extract structured intake data from the customer's natural message
- summarize the customer's original message
- detect missing required intake fields
- ask the next missing question

## Forbidden LLM Responsibilities

LLM must not:

- diagnose vehicle problems
- recommend repairs
- estimate prices
- promise appointment availability
- replace staff confirmation
- act as a general-purpose chatbot

Any wording that implies diagnosis, repair certainty, pricing certainty, or confirmed scheduling violates this spec.

## Customer Flow

1. Customer enters a natural first message.
2. System extracts what it can from that message.
3. System shows a short confirmation summary.
4. System asks only for missing required information.
5. Customer submits the intake request.
6. System shows the submitted-state message.
7. Staff confirms details and visit time, preferably by phone when confirmation risk is high.

Example first message:

```txt
Opel Insignia, check engine light came on, maybe sensors, when can I come?
```

Submitted-state message:

```txt
Request received. A service advisor will contact you to confirm details and visit time.
```

## Required UX Behavior

- Prefer a free-text first message over a rigid question-answer flow.
- Do not force the customer to re-enter data already extracted.
- Show extracted information in a short confirmation summary.
- Ask only for missing required fields.
- Use examples, suggestions, or animated placeholder behavior to teach useful first-message content.
- Present the customer-facing flow as a conversation or timeline rather than a traditional form.
- Keep staff confirmation visible as the final authority for appointment details.

## Architecture Rules

- Keep future LLM calls behind a small intake extraction boundary.
- Do not call LLM providers directly from controllers.
- Do not call LLM providers directly from Vue/Inertia components.
- Do not mix AI extraction with scheduling, pricing, repair recommendation, or staff confirmation workflows.
- Backend and frontend leads must not implement LLM behavior unless a spec explicitly asks for it.

## Out of Scope

- AI diagnosis
- repair recommendations
- price estimation
- appointment availability promises
- general-purpose chatbot behavior
- automatic staff replacement for confirmation decisions
