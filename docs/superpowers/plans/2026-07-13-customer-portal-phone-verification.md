# Customer Portal Phone Verification Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement secure passwordless verified-phone access to a protected `/my-requests` placeholder without Customer User accounts.

**Architecture:** A focused `CustomerPortal` domain owns OTP issuance and verification. Laravel HTTP classes own validation, routing, rate limiting, and session state; an Eloquent challenge record provides hashed, expiring, attempt-limited, atomic single-use codes.

**Tech Stack:** Laravel 12, PHP 8.2+, PostgreSQL, Inertia 2, Vue 3, TypeScript, PHPUnit.

## Global Constraints

- Customer remains a workshop Customer, not a User; create no account or password.
- Never query customer/request records during this verification slice or reveal whether they exist.
- Normalize phone values with `App\Domain\Shared\ValueObjects\Phone`.
- Store only a Laravel hash of the six-digit OTP; lifetime is 10 minutes and maximum attempts is 5.
- Verified-phone session lifetime is 30 minutes and is enforced explicitly.
- Request limits are 5/minute per normalized phone and 20/hour per IP; verification is 10/minute per pending challenge/IP.
- Use only the local/log provider in local development and a fake provider in tests; fail closed elsewhere.
- Keep controllers and requests in `app/Http`, business logic in `app/Domain/CustomerPortal`, and Eloquent models in `app/Models`.
- Preserve all unrelated dirty-worktree changes.

---

### Task 1: Domain, persistence, HTTP flow, and feature tests

**Files:**
- Create: `app/Domain/CustomerPortal/Contracts/OtpProvider.php`
- Create: `app/Domain/CustomerPortal/Providers/LogOtpProvider.php`
- Create: `app/Domain/CustomerPortal/Actions/RequestPhoneVerificationAction.php`
- Create: `app/Domain/CustomerPortal/Actions/VerifyPhoneCodeAction.php`
- Create: focused result/exception classes only where they make the two actions clearer
- Create: `app/Models/CustomerPhoneVerification.php`
- Create: `database/migrations/2026_07_13_000001_create_customer_phone_verifications_table.php`
- Create: `config/customer_portal.php`
- Create: `app/Http/Requests/RequestCustomerPortalCodeRequest.php`
- Create: `app/Http/Requests/VerifyCustomerPortalCodeRequest.php`
- Create: Customer Portal HTTP controller(s) under `app/Http/Controllers`
- Create: `app/Http/Middleware/EnsureVerifiedCustomerPhone.php`
- Create: `tests/Fakes/FakeOtpProvider.php`
- Create: `tests/Feature/CustomerPortalAccessTest.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `routes/web.php`

**Interfaces:**
- `OtpProvider::send(string $normalizedPhone, string $code): void`
- `RequestPhoneVerificationAction::handle(string $rawPhone): CustomerPhoneVerification`
- `VerifyPhoneCodeAction::handle(int $challengeId, string $code): string` returns the normalized verified phone or raises one generic domain failure.
- Pending session keys contain the challenge ID and normalized phone; verified keys contain normalized phone and an expiry timestamp.

- [ ] **Step 1: Write failing feature tests**

Cover request normalization, `Hash::check()` against persisted `code_hash`, absence of raw OTP in every persisted attribute, successful session creation, invalid attempts, expiry, exhaustion, reuse, previous-code invalidation, route throttles, protected placeholder access, explicit session expiry, and unchanged `users` and `customers` counts. Bind `FakeOtpProvider` in test setup and obtain the raw code only from the fake.

- [ ] **Step 2: Run the focused test and verify RED**

Run: `php artisan test tests/Feature/CustomerPortalAccessTest.php`

Expected: failure because Customer Portal routes and classes do not exist.

- [ ] **Step 3: Add the minimal persistence and provider boundary**

Create the challenge table/model/config, the provider contract, and a local-only logger implementation. Hide `code_hash` from serialization and cast all timestamps.

- [ ] **Step 4: Add request and verification actions**

Generate the code with `random_int()`, zero-pad to six digits, hash with `Hash::make()`, invalidate previous live challenges, and dispatch only after persistence succeeds. Verify under `DB::transaction()` and `lockForUpdate()`; return a failure status from the transaction so an invalid-attempt increment commits before a generic exception is raised.

- [ ] **Step 5: Add HTTP validation, sessions, middleware, rate limiters, and routes**

Use FormRequests for phone/code validation. Regenerate the session ID after successful verification, store the normalized phone and explicit expiry, and protect `/my-requests`. Register named rate limiters with hashed phone/challenge keys and place all routes on the public surface.

- [ ] **Step 6: Run the focused test and verify GREEN**

Run: `php artisan test tests/Feature/CustomerPortalAccessTest.php`

Expected: all Customer Portal feature tests pass.

### Task 2: Customer Portal public pages

**Files:**
- Create: `resources/js/layouts/CustomerPortalLayout.vue`
- Create: `resources/js/pages/CustomerPortal/RequestAccess.vue`
- Create: `resources/js/pages/CustomerPortal/VerifyCode.vue`
- Create: `resources/js/pages/CustomerPortal/Index.vue`
- Modify: the Customer Portal controllers from Task 1 only as needed for Inertia props.

**Interfaces:**
- Access page receives optional `sessionExpired: boolean`.
- Verify page receives `maskedPhone: string` and neutral status/error state through Laravel/Inertia.
- Placeholder receives no request records; masked verified phone is optional.

- [ ] **Step 1: Add or extend failing response assertions**

Assert exact Inertia components and privacy-safe props for access, verification, protected placeholder, and expired-session recovery.

- [ ] **Step 2: Run the focused feature test and verify RED**

Run: `php artisan test tests/Feature/CustomerPortalAccessTest.php`

Expected: failure because the expected page components/props are not implemented.

- [ ] **Step 3: Implement the shared layout and three pages**

Reuse the established public cream background, teal CTA, white bordered card, slate typography, responsive `max-w-md` layout, and existing UI primitives. Use visible labels, `aria-invalid`, `aria-describedby`, `aria-live`, `autocomplete="tel"`, and `autocomplete="one-time-code"`. Avoid account/login and record-existence language.

- [ ] **Step 4: Run focused tests and frontend build**

Run: `php artisan test tests/Feature/CustomerPortalAccessTest.php`

Run when `node_modules` is present: `npm run build`

Expected: feature tests pass and Vite exits successfully.

### Task 3: Documentation, complete verification, and review

**Files:**
- Modify: `docs/architecture/autoservice-ddd-rules.md`
- Modify: `.ai/task-report.md`

- [ ] **Step 1: Update the architecture rule**

Document Customer Portal as planned, using short-lived verified-phone sessions while Customer remains distinct from User and has no account, password, or credentials.

- [ ] **Step 2: Inspect the scoped diff and run complete verification**

Run: `git diff -- <all scoped files>`

Run: `php artisan test tests/Feature/CustomerPortalAccessTest.php`

Run: `php artisan test`

Run when dependencies are present: `npm run build`

- [ ] **Step 3: Perform the autoservice-reviewer pass**

Check the actual diff for enumeration leaks, accidental Customer/User creation, raw-code persistence, rolled-back attempt counters, non-atomic reuse, missing expiry enforcement, plaintext PII limiter keys, HTTP/domain responsibility leaks, frontend type/store excess, and unrelated changes.

- [ ] **Step 4: Update the task report**

Record every changed file, session/OTP design, routes, architecture decisions, tradeoffs, tests with exact results, risks, and focused follow-ups only.
