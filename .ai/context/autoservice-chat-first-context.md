# AutoService Chat-First Context

This file captures current product and implementation decisions for AutoService agents.

## Product Vision

AutoService is a chat-first auto service intake and workshop management platform.

The public customer experience should feel like sending a natural message to a service advisor, not filling out a rigid booking form. The workshop side should turn those messages into actionable intake records, customer history, vehicles, and repair orders.

## Chat-First Intake Direction

Customer intake starts with one free-text message, for example:

```txt
Opel Insignia, check engine light came on, maybe sensors, when can I come?
```

The system should preserve the original message, extract only safe structured details, summarize what the customer actually said, and ask only for missing required information. Do not force the customer to re-enter details already present in the message.

Missing-field priority is:

1. phone
2. vehicle
3. preferred time

Phone is the primary intake identity because the workshop needs it to contact the customer.

## MVP Scope

Current MVP priority is:

- public chat-first intake entry point
- `BookingRequest` persistence with original message preserved
- safe intake extraction boundary
- workshop dashboard for reviewing and changing request status
- customer and vehicle history for workshop staff
- repair order creation from confirmed booking requests

Do not expand into diagnosis, pricing, real-time scheduling, or general chatbot behavior.

## Current Stack

- Laravel 12
- Inertia Laravel 2
- Vue 3 with TypeScript
- Vite
- Tailwind CSS
- authenticated workshop dashboard with active workshop membership middleware

## Current Domain Entities

Current core models include:

- `User`
- `Workshop`
- `WorkshopUser`
- `Customer`
- `Vehicle`
- `BookingRequest`
- `RepairOrder`

Workshop-scoped dashboard behavior must use active `WorkshopUser` membership. Do not scope by a direct `user.workshop_id`.

## BookingRequest As Intake Aggregate

`BookingRequest` is the intake aggregate for now.

It owns the submitted intake record and links to:

- workshop, always for public intake through `/w/{workshop:slug}`
- customer, when known
- vehicle, when known
- creating user, when staff-created
- repair order, when converted

Important fields include:

- `original_message`
- `problem_description`
- `customer_phone`
- `preferred_date`
- `status`

The public chat-first slice stores the original message and problem description, sets `workshop_id` from the route workshop, and keeps customer and vehicle nullable.

## AI Boundaries

AI may be used only as an intake extraction assistant.

Allowed:

- extract structured intake data from customer text
- summarize the customer's original message
- detect missing required fields
- ask the next missing question

Not allowed:

- diagnose vehicle problems
- recommend repairs
- estimate prices
- promise appointment availability
- replace staff confirmation
- act as a general-purpose chatbot

Keep LLM calls behind `IntakeExtractorInterface`. The current fallback extractor is conservative: it may preserve the original text and extract narrow low-risk data such as phone, but must not pretend to understand vehicle diagnosis or availability.

## Rejected Entities And Features

Do not introduce these unless a current, concrete problem requires them:

- `Chat`
- `Conversation`
- `AiThread`
- workflow engine
- queue-first intake pipeline
- AI mechanic behavior
- customer self-scheduling
- online price estimation
- repair recommendations
- public workshop-card browsing as the main landing flow

Patterns must pay rent.

## Public Intake Flow

Each workshop public intake page should focus on chat-first intake:

- header
- hero
- intake textarea
- example chips or suggestion behavior
- submit state
- received state

After submit, show:

```txt
Request received. A service advisor will contact you to confirm details and visit time.
```

Canonical public intake routes are:

```txt
GET  /w/{workshop:slug}
POST /w/{workshop:slug}/intake
```

The root `/` page is SaaS marketing/home, not a workshop-less intake submission endpoint.

The old workshop-specific `/book/{slug}` form may exist in code, but the product direction is chat-first public intake through `/w/{workshop:slug}`.

## Appointment Policy

Appointment availability is not promised by the public UI or AI.

Customers may state a preferred time. Staff must confirm the final visit time, preferably by phone. Booking request statuses can move through new, confirmed, rejected, or cancelled, but confirmation remains a staff responsibility.

## Customer Account Strategy

Customers do not need public accounts for MVP intake.

Phone is the safest lightweight identity for matching and follow-up. Workshop staff accounts manage dashboard access through `WorkshopUser` membership.
