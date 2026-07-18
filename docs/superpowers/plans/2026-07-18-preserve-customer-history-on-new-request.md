# Preserve Customer History on New Request Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Keep verified customer request history visible on the New Request homepage and replace the starter textarea's browser-blue focus outline with the branded focus treatment.

**Architecture:** `PublicIntakeController` validates the short-lived phone-verification session and reuses `CustomerRequestIndexQuery` to provide safe recent summaries. `Welcome.vue` conditionally fills the existing workspace history slot, while `PublicIntakeFlow.vue` suppresses only the native blue outline and retains its teal focus ring.

**Tech Stack:** Laravel, Inertia.js, Vue 3, Tailwind CSS, PHPUnit, Vitest

## Global Constraints

- Never expose the verified phone in Inertia props.
- Never query or render another phone's requests.
- Missing, malformed, or expired verification keeps the existing `My requests` fallback.
- Empty verified history keeps the existing `My requests` fallback.
- Preserve a visible branded keyboard focus indicator.
- Do not change intake submission or customer verification rules.

---

### Task 1: Provide verified history to the homepage

**Files:**
- Modify: `tests/Feature/PublicIntakeSubmissionTest.php`
- Modify: `app/Http/Controllers/PublicIntakeController.php`

**Interfaces:**
- Consumes: session keys `customer_portal.verified_phone` and `customer_portal.verified_until`
- Produces: optional Inertia props `recentRequests` and `hasMoreRequests`

- [x] **Step 1: Add failing feature tests**

Add one test with two booking requests on different normalized phones and an active verified session. Assert `/` receives only the owned summary, `hasMoreRequests` is false, and phone props are missing. Add one test with an expired verification timestamp and assert both history props are missing.

- [x] **Step 2: Verify RED**

Run: `php artisan test tests/Feature/PublicIntakeSubmissionTest.php --filter='homepage_(exposes|omits)_verified_customer_history'`

Expected: FAIL because the Welcome response does not provide verified history.

- [x] **Step 3: Implement scoped history props**

Inject `Illuminate\Http\Request` and `CustomerRequestIndexQuery` into `create`. Resolve a phone only when it is a non-empty string and `verified_until` is an integer later than `now()->timestamp`. Query by that phone and merge only `recentRequests` and `hasMoreRequests` into the Welcome props.

- [x] **Step 4: Verify GREEN**

Run the same filtered feature-test command.

Expected: both tests PASS.

### Task 2: Render history and remove the native blue focus outline

**Files:**
- Modify: `resources/js/pages/Welcome.test.ts`
- Modify: `resources/js/pages/Welcome.vue`
- Modify: `resources/js/components/public-intake/PublicIntakeFlow.test.ts`
- Modify: `resources/js/components/public-intake/PublicIntakeFlow.vue`

**Interfaces:**
- Consumes: optional `recentRequests: CustomerRequestSummary[]` and `hasMoreRequests: boolean`
- Produces: conditional `history` slot content using `CustomerRequestHistory`

- [x] **Step 1: Add failing frontend tests**

Assert Welcome imports `CustomerRequestHistory`, declares the optional history props, conditionally provides `#history` only when requests exist, and binds the existing component. Assert the starter textarea classes include `focus:outline-none` while retaining `focus:ring-[#0e7c86]/25`.

- [x] **Step 2: Verify RED**

Run: `npm test -- resources/js/pages/Welcome.test.ts resources/js/components/public-intake/PublicIntakeFlow.test.ts`

Expected: FAIL because Welcome has no history slot and the textarea lacks native-outline suppression.

- [x] **Step 3: Implement the frontend changes**

Import the existing history component and summary type, declare the optional props, and add a conditional named slot before the Welcome content. Add `focus:outline-none` to both starter and expanded textarea class variants without removing the teal ring classes.

- [x] **Step 4: Run focused verification**

Run:

```bash
php artisan test tests/Feature/PublicIntakeSubmissionTest.php
npm test -- resources/js/pages/Welcome.test.ts resources/js/components/public-intake/PublicIntakeFlow.test.ts
npx prettier --check resources/js/pages/Welcome.vue resources/js/pages/Welcome.test.ts resources/js/components/public-intake/PublicIntakeFlow.vue resources/js/components/public-intake/PublicIntakeFlow.test.ts
git diff --check -- app/Http/Controllers/PublicIntakeController.php tests/Feature/PublicIntakeSubmissionTest.php resources/js/pages/Welcome.vue resources/js/pages/Welcome.test.ts resources/js/components/public-intake/PublicIntakeFlow.vue resources/js/components/public-intake/PublicIntakeFlow.test.ts
```

Expected: all focused tests and formatting checks PASS with no whitespace errors.
