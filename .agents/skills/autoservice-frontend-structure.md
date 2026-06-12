# AutoService Frontend Structure

## Purpose

Use this skill for Vue/Inertia frontend implementation and review.

The goal is to keep frontend code understandable, typed, componentized, and free from god components or unnecessary stores.

## Page component rule

Page components should orchestrate:

- layout
- top-level props
- feature components
- page-level actions

Page components should not contain large tables, complex modals, repeated card markup, or heavy formatting logic.

## Feature component rule

Extract large UI sections into feature components.

Examples:

```txt
resources/js/components/dashboard/BookingRequestTable.vue
resources/js/components/dashboard/BookingRequestStatusBadge.vue
resources/js/components/dashboard/BookingRequestEmptyState.vue
resources/js/components/dashboard/modals/RejectBookingRequestModal.vue
```

Use feature-local components until a component is reused across multiple features.

## Shared UI rule

Only move components to common/shared UI folders when they are truly reused by multiple features.

Do not create global shared components from a single use case.

## Store rule

Do not introduce Pinia/Vuex stores for page-local Inertia props.

Use props for page data:

- booking requests list
- customer detail
- vehicle detail
- repair order detail

Use stores only for shared application state:

- authenticated user
- active workshop when needed globally
- theme
- locale
- sidebar state
- notifications/toasts
- permissions when reused across layout/features

## TypeScript rule

Use `type` for:

- Inertia DTOs
- props
- union values
- composed shapes
- nullable value aliases

Use `interface` for:

- extensible object contracts
- public API-like contracts where extension is expected

Keep feature-specific types near the feature:

```txt
resources/js/components/<feature>/types.ts
```

Avoid duplicated inline prop types across components.

## Formatting/helper rule

If a helper is used by multiple components in a feature, move it to feature-local `utils.ts`.

Do not create global utility files for single-feature helpers.

## Modal/popup rule

Modal and popup components must live near the feature they belong to.

Examples:

```txt
components/dashboard/modals/
components/customers/modals/
```

Do not embed large modal markup inside page components.

## Review checklist

Flag:

- page components growing into god components
- stores used for page-local props
- duplicated inline TypeScript shapes
- business/server logic recreated on frontend
- feature-specific components prematurely moved to shared UI
- large modals embedded in page files
- frontend state that conflicts with backend/Inertia source of truth
# Agent Quality Update

Feature-local type extraction:
- If the same DTO appears in both a page and a feature component, extract that type near the feature.
- Do not duplicate frontend DTO shapes across page/component boundaries.
- Keep shared UI types only when multiple features reuse them.

Validation:
- After adding a Vue page or component, require a frontend smoke/build check when `EXECUTION MODE` is enabled and dependencies are available.
- Use `npm run build` only when dependencies are already installed.
- If build cannot run because dependencies are missing, report environment risk instead of claiming code success.
