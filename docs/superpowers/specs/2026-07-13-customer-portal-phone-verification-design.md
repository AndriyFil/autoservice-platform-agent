# Customer Portal Phone Verification Design

## Goal

Add a global `/my-requests` entry point where a workshop Customer can prove possession of a phone number without becoming a platform User and without creating an account or password.

## Scope

This slice includes phone entry, OTP issuance through a provider boundary, code verification, a short-lived verified-phone session, a protected placeholder page, expired-session recovery, rate limiting, and automated tests. It does not list requests or expose any Customer, BookingRequest, RepairOrder, Estimate, or Document data.

## Architecture

Phone possession verification belongs to `app/Domain/CustomerPortal`. HTTP controllers, FormRequests, and middleware remain in `app/Http`; the Eloquent challenge model remains in `app/Models` under the project DDD rules.

`RequestPhoneVerificationAction` normalizes input with `Phone`, invalidates previous live challenges for that normalized phone, creates a six-digit raw code with a cryptographically secure generator, stores only its Laravel hash, and sends the raw code through `OtpProvider`. `VerifyPhoneCodeAction` locks the challenge row in a transaction, commits failed attempt increments, rejects expired/invalidated/consumed/exhausted challenges, and consumes a valid challenge exactly once.

`LogOtpProvider` is available only in the local environment. Tests replace `OtpProvider` with `FakeOtpProvider`. Other environments fail closed until a paid provider is intentionally configured.

## Persistence and Timing

`customer_phone_verifications` stores normalized phone, code hash, expiry, attempts, invalidation time, consumption time, and timestamps. Defaults are a six-digit code, ten-minute code lifetime, five verification attempts, and a thirty-minute verified session.

The successful HTTP flow regenerates the session ID, removes pending verification state, and stores `customer_portal.verified_phone` plus `customer_portal.verified_until`. Middleware enforces the explicit deadline independently from the application-wide session lifetime.

## Routes

- `GET /my-requests/access`: phone entry page.
- `POST /my-requests/access`: request a code with request rate limits.
- `GET /my-requests/verify`: code entry page for a pending challenge.
- `POST /my-requests/verify`: verify a code with verification rate limits.
- `GET /my-requests`: protected placeholder page.

An unverified or expired visit to `/my-requests` redirects to the access page. Expired verified state produces neutral recovery copy.

## Rate Limits

Code requests are limited to five per minute per normalized phone and twenty per hour per IP. Verification submissions are limited to ten per minute per pending challenge and IP. Phone numbers are hashed before being placed in limiter keys.

## Privacy and Security

Every syntactically valid phone follows the same challenge and response path. The verification slice never queries Customer, BookingRequest, or User records, so it cannot disclose whether the phone has records. Customer-facing errors do not distinguish invalid, expired, reused, invalidated, or exhausted codes.

Raw OTP values exist only long enough to pass to the provider. They are never persisted. Database locking and consumption timestamps provide atomic single-use behavior.

## Frontend

Three small Inertia pages use a shared Customer Portal public layout based on the existing cream, teal, white-card, and slate design. The code screen uses one accessible input with numeric input mode and one-time-code autocomplete. The placeholder says only that the phone is verified and that request details will be available later; it does not imply whether records exist.

## Tests

Feature tests cover normalization, hashed persistence, provider delivery, valid sessions, invalid/expired/reused/exhausted codes, previous-code invalidation, request and verify throttles, protected access, expired sessions, neutral behavior, and the absence of User or Customer creation.
