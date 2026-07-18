# Public Customer Experience Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Apply one premium, calm public design system to the intake, customer portal, and staff authentication journeys without changing their behavior.

**Architecture:** Add public-only visual primitives and CSS tokens, then compose them from the existing Inertia pages and layouts. Keep the intake state machine, Inertia form ownership, routes, and backend payloads unchanged; tests enforce both the visual contract and existing flow behavior.

**Tech Stack:** Laravel, Inertia.js, Vue 3, TypeScript, Tailwind CSS 3, Lucide Vue, Vitest.

## Global Constraints

- Warm ivory page background, pure-white surfaces, dark navy primary text/actions, and petrol/teal brand accents/focus rings.
- No gradients, glassmorphism, glossy effects, fake ratings, fake availability, or unsupported response-time claims.
- Instrument Sans remains the public typeface.
- Mobile interactive targets are at least 44px high and workshop cards stack vertically.
- Existing Laravel, Inertia, validation, authentication, customer portal, and BookingRequest behavior remains unchanged.
- Do not modify internal dashboard styling or global UI primitives used by the dashboard.
- Preserve the existing uncommitted worktree changes and do not create implementation commits that could mix user-owned edits.

---

### Task 1: Public Visual Contract and Reusable Identity

**Files:**
- Create: `resources/js/components/public/PublicBrand.vue`
- Create: `resources/js/components/public/PublicHeader.vue`
- Modify: `resources/css/app.css`
- Create: `resources/js/pages/PublicExperienceDesign.test.ts`

**Interfaces:**
- Produces: `PublicBrand` with optional `compact?: boolean` and `PublicHeader` with `canLogin?: boolean`, `canRegister?: boolean`, `adminLoginUrl?: string`, and `adminRegisterUrl?: string`.
- Produces CSS utilities: `.public-page`, `.public-card`, `.public-focus`, `.public-field`, `.public-button-primary`, `.public-button-secondary`, and `.public-kicker`.

- [ ] **Step 1: Write the failing visual-contract test**

Add source assertions that require the public CSS tokens/classes, AutoService identity, responsive navigation, navy primary CTA, and petrol focus treatment:

```ts
expect(css).toContain('--public-ivory: #f7f4ed');
expect(css).toContain('--public-navy: #0b1f33');
expect(css).toContain('--public-brand: #0e7c86');
expect(css).toContain('.public-button-primary');
expect(brand).toContain('AutoService');
expect(header).toContain('My requests');
expect(header).toContain('Create workshop account');
expect(header).toContain('focus-visible:ring-[#0e7c86]');
```

- [ ] **Step 2: Run the test and verify RED**

Run: `npm test -- resources/js/pages/PublicExperienceDesign.test.ts`

Expected: FAIL because the new public components and CSS contract do not exist.

- [ ] **Step 3: Implement the visual primitives**

Add scoped public CSS component classes backed by exact public color variables. Implement `PublicBrand` with the existing wrench motif in a petrol rounded mark and `PublicHeader` with desktop links plus a compact mobile menu using semantic links and petrol keyboard focus rings. Do not change shared `Button` or `Input` variants.

- [ ] **Step 4: Run the test and verify GREEN**

Run: `npm test -- resources/js/pages/PublicExperienceDesign.test.ts`

Expected: PASS.

### Task 2: Public Intake Shell, Conversation, and Trust Panel

**Files:**
- Create: `resources/js/components/public-intake/PublicTrustPanel.vue`
- Modify: `resources/js/pages/Welcome.vue`
- Modify: `resources/js/components/public-intake/PublicIntakeFlow.vue`
- Modify: `resources/js/components/public-intake/PublicIntakeSuccess.vue`
- Modify: `resources/js/pages/Welcome.test.ts`

**Interfaces:**
- Consumes: `PublicHeader`, `PublicBrand`, and the public CSS utility classes from Task 1.
- Produces: `PublicTrustPanel`, which renders only request-sharing, contact-expectation, and pre-submission safety statements.
- Preserves: `PublicIntakeFlow` props `{ workshops: WorkshopOption[] }` and all existing state/action function calls.

- [ ] **Step 1: Add failing intake design assertions**

Require the shared header, two-column desktop shell, trust panel, ivory page, navy CTA, petrol selected-card state, stacked mobile workshop grid, and absence of unsupported claims:

```ts
expect(welcomeSource).toContain('PublicHeader');
expect(welcomeSource).toContain('PublicTrustPanel');
expect(welcomeSource).toContain('lg:grid-cols-[minmax(0,1fr)_18rem]');
expect(flowSource).toContain('public-button-primary');
expect(flowSource).toContain("border-[#0e7c86]");
expect(flowSource).toContain('sm:grid-cols-2');
expect(flowSource).not.toMatch(/rating|available today|reply within/i);
```

Keep every existing behavioral assertion for one active control, one final post, editability, cancellation, radio-card semantics, and success actions.

- [ ] **Step 2: Run the intake tests and verify RED**

Run: `npm test -- resources/js/pages/Welcome.test.ts resources/js/components/public-intake/state.test.ts`

Expected: the new design assertions FAIL while state tests PASS.

- [ ] **Step 3: Implement the redesigned intake**

Replace the page header with `PublicHeader`. Compose the intake and truthful trust panel in a responsive grid. Restyle conversation prompts, answer bubbles, active composers, optional actions, vehicle editor, workshop cards, confirmation, cancel confirmation, and success state using the public system. Preserve all IDs, `v-model`s, event handlers, state conditions, accessibility attributes, and the single `form.post` call.

- [ ] **Step 4: Run the intake tests and verify GREEN**

Run: `npm test -- resources/js/pages/Welcome.test.ts resources/js/components/public-intake/state.test.ts`

Expected: PASS with all existing behavioral coverage retained.

### Task 3: Customer Portal Journey

**Files:**
- Modify: `resources/js/layouts/CustomerPortalLayout.vue`
- Modify: `resources/js/pages/CustomerPortal/RequestAccess.vue`
- Modify: `resources/js/pages/CustomerPortal/VerifyCode.vue`
- Modify: `resources/js/pages/CustomerPortal/Index.vue`
- Modify: `resources/js/pages/CustomerPortal.test.ts`

**Interfaces:**
- Consumes: `PublicBrand` and the public CSS utility classes from Task 1.
- Preserves: all current page props, Inertia form payloads, routes, and privacy-safe wording.

- [ ] **Step 1: Add failing portal design assertions**

```ts
expect(layout).toContain('PublicBrand');
expect(layout).toContain('public-page');
expect(layout).toContain('rounded-[1.75rem]');
expect(requestAccess).toContain('public-field');
expect(requestAccess).toContain('public-button-primary');
expect(verifyCode).toContain('tracking-[0.32em]');
expect(index).toContain('public-button-secondary');
```

Retain assertions for tel autocomplete, one-time-code semantics, masked phone, session expiration, routes, and the absence of account/record-existence leaks.

- [ ] **Step 2: Run the portal test and verify RED**

Run: `npm test -- resources/js/pages/CustomerPortal.test.ts`

Expected: FAIL on the new design assertions.

- [ ] **Step 3: Implement the portal redesign**

Use the shared public identity and ivory shell, enlarge the white portal card and touch targets, apply navy CTAs and petrol focus rings, and unify semantic notice styles. Keep the verified placeholder factual and unchanged in capability.

- [ ] **Step 4: Run the portal test and verify GREEN**

Run: `npm test -- resources/js/pages/CustomerPortal.test.ts`

Expected: PASS.

### Task 4: Staff Authentication Journey

**Files:**
- Modify: `resources/js/layouts/auth/AuthSimpleLayout.vue`
- Modify: `resources/js/pages/auth/Login.vue`
- Modify: `resources/js/pages/auth/Register.vue`
- Modify: `resources/js/pages/auth/ForgotPassword.vue`
- Modify: `resources/js/pages/auth/ResetPassword.vue`
- Modify: `resources/js/pages/auth/VerifyEmail.vue`
- Modify: `resources/js/pages/auth/ConfirmPassword.vue`
- Create: `resources/js/pages/AuthPublicDesign.test.ts`

**Interfaces:**
- Consumes: `PublicBrand` and public CSS utility classes from Task 1.
- Preserves: `AuthLayout` slot contract `{ title?: string; description?: string }` and every existing form field, route, processing state, and redirect behavior.

- [ ] **Step 1: Write the failing auth visual test**

```ts
expect(layout).toContain('PublicBrand');
expect(layout).toContain('public-page');
expect(layout).toContain('public-card');
for (const page of authPages) {
    expect(page).toContain('public-button-primary');
}
expect(login).toContain('Workshop staff');
expect(register).toContain('Workshop account');
```

Also assert the existing route names and form inputs remain present on each page.

- [ ] **Step 2: Run the auth design test and verify RED**

Run: `npm test -- resources/js/pages/AuthPublicDesign.test.ts`

Expected: FAIL because auth pages still use the starter-kit visual language.

- [ ] **Step 3: Implement the auth redesign**

Rebuild `AuthSimpleLayout` as a centered public ivory shell with AutoService identity and a generous white card. Apply public fields, buttons, labels, notices, and links to every reachable auth page. Add staff-context eyebrows without changing titles, route names, inputs, or Inertia submission handlers.

- [ ] **Step 4: Run the auth design and backend feature tests**

Run: `npm test -- resources/js/pages/AuthPublicDesign.test.ts`

Expected: PASS.

Run: `php artisan test tests/Feature/Auth`

Expected: PASS with existing authentication behavior unchanged.

### Task 5: Responsive and Regression Verification

**Files:**
- Modify only files from Tasks 1–4 if verification exposes a defect.

**Interfaces:**
- Consumes: completed public design system and all existing public behavior.
- Produces: verified formatted and type-safe frontend changes.

- [ ] **Step 1: Run focused frontend tests**

Run: `npm test -- resources/js/pages/PublicExperienceDesign.test.ts resources/js/pages/Welcome.test.ts resources/js/components/public-intake/state.test.ts resources/js/pages/CustomerPortal.test.ts resources/js/pages/AuthPublicDesign.test.ts`

Expected: PASS with zero failures.

- [ ] **Step 2: Run focused backend public/auth tests**

Run: `php artisan test tests/Feature/PublicIntakeSubmissionTest.php tests/Feature/CustomerPortalAccessTest.php tests/Feature/Auth`

Expected: PASS with zero failures.

- [ ] **Step 3: Check formatting and types**

Run: `npx prettier --check resources/css/app.css resources/js/components/public resources/js/components/public-intake resources/js/layouts/CustomerPortalLayout.vue resources/js/layouts/auth/AuthSimpleLayout.vue resources/js/pages/Welcome.vue resources/js/pages/CustomerPortal resources/js/pages/auth resources/js/pages/PublicExperienceDesign.test.ts resources/js/pages/AuthPublicDesign.test.ts`

Expected: all matched files use Prettier formatting.

Run: `npx vue-tsc --noEmit`

Expected: exit code 0.

- [ ] **Step 4: Run a production frontend build**

Run: `npm run build`

Expected: Vite build exits successfully.

- [ ] **Step 5: Perform final self-review**

Run: `git diff --check` and inspect `git diff` only for the public files in this plan. Confirm no backend, internal dashboard, route, domain, or persistence file was changed by this implementation; confirm no gradients or unsupported claims appear in public UI source.
