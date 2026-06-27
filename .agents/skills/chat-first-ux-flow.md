# Chat-First UX Flow

## Purpose

Use this skill when designing customer intake flows where the customer starts by typing their problem instead of filling a long form.

The flow should feel like a helpful service conversation while still producing stable data that can become AutoService backend entities.

## Core Principle

Prefer conversation.
Fall back to forms only when conversation becomes inefficient.
Start with the customer's words.
Ask for structured details only when needed, one question at a time, and keep each step understandable without exposing internal system terms.

## Flow Rules

- Begin with a natural-language problem statement.
- Ask one question at a time.
- Collect information progressively.
- Keep user input natural-language first.
- Prefer confirmation over forcing the customer to re-enter information.
- Avoid long forms during the first contact.
- Keep the flow independent from the presentation channel.
- Keep backend model changes stable where possible.

## Customer Language

Do not expose internal terms such as:

- `BookingRequest`
- `RepairOrder`
- internal workflow status names
- database field names
- implementation-only identifiers

Use customer-facing language instead:

- "request"
- "visit"
- "service details"
- "car"
- "problem"
- "preferred time"
- "contact details"

## Progressive Intake Order

Use this order unless the feature needs a clearly different path:

1. Ask what is wrong or what service the customer needs.
2. Ask for the vehicle only after the problem is known.
3. Ask for timing preferences after the service need is clear.
4. Ask for contact details when the customer is ready to submit or continue later.
5. Confirm a short summary before creating or updating backend records.

## Backend Mapping

Design the conversation so it can be converted into existing backend entities:

- `Customer`: contact identity and preferred communication details.
- `Vehicle`: make, model, year, plate/VIN only when needed.
- `BookingRequest`: customer request, preferred time, initial service intent.
- `RepairOrder`: workshop-owned repair workflow after the request is accepted or converted.

Keep this mapping internal. The customer should see a clear service request, not backend model names.

## Output Expectations

When using this skill, include:

- entry prompt
- question sequence
- customer-facing summary
- internal data mapping
- fallback path for unknown answers
- handoff notes for website chat, Telegram bot, or customer portal

## Anti-Patterns

- Starting with a long intake form.
- Asking for every vehicle detail before understanding the problem.
- Asking multiple unrelated questions in one message.
- Making the customer choose internal service categories too early.
- Exposing backend terms in labels, messages, or confirmations.
- Changing backend models only to fit one chat screen.
# Chat-First Intake Product Boundary

AutoService is a chat-first auto service intake and workshop management platform.

This flow is not an AI mechanic. It is an AI intake assistant. The assistant helps collect and structure the customer's request so a service advisor can confirm details and visit time.

LLM is allowed only for:

- extracting structured intake data from customer text
- summarizing the customer's original message
- detecting missing required fields
- asking the next missing question

LLM must not:

- diagnose vehicle problems
- recommend repairs
- estimate prices
- promise appointment availability
- replace staff confirmation
- act as a general-purpose chatbot

## UX Rules

Prefer a free-text first message over a rigid question-answer flow.

Example first message:

```txt
Opel Insignia, check engine light came on, maybe sensors, when can I come?
```

The customer flow should:

1. Let the customer describe the request naturally.
2. Extract what can be understood from the first message.
3. Show a short confirmation summary.
4. Ask only for missing required information.
5. Avoid asking the customer to re-enter data already extracted.
6. Submit the request for staff confirmation.

The UI must use examples, suggestions, or animated placeholder behavior to teach what a useful first message can include.

The submitted state must say:

```txt
Request received. A service advisor will contact you to confirm details and visit time.
```

Phone call remains the safest final confirmation channel.

Customer-facing intake should look like a conversation or timeline, not a traditional form.
