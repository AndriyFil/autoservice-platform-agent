# Public Customer Experience Redesign

**Date:** 2026-07-16

## Goal

Redesign every externally visible AutoService surface referenced by the public header and customer journey so the product feels calm, premium, trustworthy, and effortless. Preserve all existing Laravel, Inertia, validation, authentication, customer portal, and booking-request behavior.

## Scope

The redesign covers:

- Global public intake and its submitted-success state.
- Customer portal phone access, verification-code, and verified placeholder pages.
- Staff login and workshop registration.
- Password recovery, password reset, email verification, and password-confirmation pages reachable from staff authentication.

Internal workshop dashboard pages, backend behavior, routes, persistence, domain logic, and product capabilities are out of scope.

## Shared Public Design System

Create an isolated public-facing visual layer rather than changing global dashboard primitives. It will provide shared page shells and consistent treatments for identity, cards, buttons, inputs, notices, spacing, and focus states.

- Warm ivory page background and pure-white surfaces.
- Dark navy primary text and primary actions.
- Petrol/teal brand accents and focus rings.
- Restrained green, amber, and red semantic states.
- Large radii, soft neutral borders, and low-contrast shadows.
- Instrument Sans with a clear hierarchy, generous line-height, and comfortable spacing.
- Minimum 44–48px interactive targets on mobile.
- No gradients, glassmorphism, glossy effects, fake ratings, fake availability, or decorative clutter.

The AutoService identity remains recognizable across intake, portal, and authentication. Desktop layouts are spacious; mobile layouts stack naturally and reduce outer spacing without reducing touch comfort.

## Public Intake

Keep the existing same-page conversation and state machine. This is not a wizard: there is no progress indicator, numbered step, or screen-per-step routing.

- A minimal header shows the AutoService identity, My requests, Staff login, and a navy Create workshop account action.
- A single white conversational surface contains the history and active response.
- Previous system prompts remain visible as compact white message cards with petrol icons.
- Previous customer answers remain visible as soft-teal messages aligned to the right and can still be edited.
- Only the response control for the active question is shown.
- Existing problem, phone, optional details, vehicle, workshop, confirmation, cancellation, and server-error behavior remains unchanged.
- Desktop includes a narrow trust panel with only defensible statements: the selected workshop receives the request, the workshop contacts the customer, and no request is created before final submission.
- On mobile, the trust content moves below the primary interaction and workshop cards stack vertically.

Workshop cards display only data supplied by the backend. With the current payload, that means workshop name plus neutral explanatory text. Selection is unmistakable through petrol border, soft petrol background, and a check indicator. The UI must not claim ratings, opening hours, response times, or availability.

## Intake Success

The submitted state uses the same public shell and a restrained green confirmation treatment. It names the selected workshop when available, explains that the workshop will call, and keeps actions for My requests and Create another request.

## Customer Portal

Portal pages use a narrow, focused version of the same public shell.

- Phone access shows one large phone field, concise privacy-safe guidance, and a full-width navy action.
- Code verification gives the six-digit field strong visual emphasis, preserves masked-phone messaging, and retains the alternate-number action.
- Expiration and validation notices use consistent amber and red states.
- The verified placeholder honestly confirms verification without inventing unavailable request history.
- A simple route back to AutoService remains visible without distracting navigation.

## Staff Authentication

Staff authentication shares the public visual language but retains standard form interaction rather than adopting the customer chat metaphor.

- Login and registration are clearly labeled as workshop staff/account surfaces.
- Login, registration, forgotten password, reset password, email verification, and password confirmation share a dedicated public-auth shell.
- Inputs, labels, errors, buttons, links, cards, spacing, and focus states match the portal and intake system.
- Existing form fields, remember-me behavior, validation, recovery flows, routes, and redirects remain unchanged.

## Accessibility and Responsive Behavior

- Preserve semantic forms, labels, fieldsets, radio inputs, live regions, and server-error routing.
- Every interactive element receives a visible petrol keyboard focus ring.
- Selected workshop state is not communicated by color alone.
- Text and semantic colors maintain readable contrast.
- Mobile layouts avoid horizontal overflow, stack cards and actions appropriately, and retain comfortable touch targets.
- Reduced-motion preferences are respected for optional transitions and scrolling where practical.

## Component Boundaries

Use small public-only components or shared classes where they prevent visual drift, especially for the logo/header, shell, trust content, form card, button, and field treatment. Keep conversation state and submission data flow in the existing intake component and state module. Do not introduce a store or alter Inertia form ownership.

## Error Handling and Data Flow

All existing client and server validation remains authoritative. Intake server errors reopen the matching conversational control. Authentication and portal errors remain next to their fields or in existing status notices. No new persistence, API calls, analytics, or external services are introduced.

## Verification

- Update focused frontend tests to cover the shared public visual contract and retain existing behavioral assertions.
- Run the public intake state and page tests, customer portal tests, and relevant auth tests/type checks available in the repository.
- Run focused formatting checks for changed frontend files.
- Perform responsive visual inspection at representative desktop and mobile widths if the local application can be rendered without changing service state.
- Review the final diff to confirm no backend or internal dashboard behavior changed.
