# Chat-First Intake Direction

## Product Position

AutoService is a chat-first auto service intake and workshop management platform.

AutoService is not an AI mechanic. It is an AI intake assistant that helps customers describe a service request naturally and helps workshops receive cleaner, structured intake information.

## AI Scope

LLM usage is allowed only for intake assistance:

- extracting structured intake data from customer text
- summarizing the customer's original message
- detecting missing required fields
- asking the next missing question

LLM usage must not:

- diagnose vehicle problems
- recommend repairs
- estimate prices
- promise appointment availability
- replace staff confirmation
- act as a general-purpose chatbot

Product Manager review must actively prevent AI diagnosis scope creep. Any feature that suggests the system knows the cause of a vehicle issue, the correct repair, the price, or confirmed availability is outside the approved product direction unless a future explicit spec changes this boundary.

## Customer Intake Flow

The customer starts with a natural first message, for example:

```txt
Opel Insignia, check engine light came on, maybe sensors, when can I come?
```

The system then:

1. Extracts any known vehicle, symptom, customer, and visit-preference details from the message.
2. Shows a short confirmation summary of what was understood.
3. Asks only for required information that is still missing.
4. Avoids forcing the customer to re-enter data already extracted.
5. Submits the request for staff review and confirmation.

After submit, the customer-facing confirmation message must be:

```txt
Request received. A service advisor will contact you to confirm details and visit time.
```

Phone call remains the safest final confirmation channel for visit time, service expectations, and any clarification that affects workshop planning.

## UX Direction

Customer-facing intake should look like a conversation or timeline, not a traditional rigid form.

The UI should teach customers what to write by using first-message examples, suggestion behavior, and animated placeholder text. These examples are guidance only; the customer should still be able to write naturally in one message.

The experience should feel lightweight:

- free-text first message before structured fields
- extracted details shown back as a short confirmation summary
- missing questions asked one at a time or in the smallest useful group
- already extracted values remain editable but are not re-requested
- submitted state clearly hands off to a service advisor

## Implementation Boundaries

LLM integration should later sit behind a small intake extraction boundary. AI calls must not be spread across controllers, Vue components, or unrelated actions.

Backend and frontend implementation must not add LLM behavior unless a spec explicitly asks for it. Until such a spec exists, teams may design the intake flow, data model, and UI states, but must keep AI behavior described as a boundary or future integration point.
