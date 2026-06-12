# AutoService Frontend Component Flow

## Purpose

Use this skill for Vue/Inertia frontend implementation and review.

The goal is to avoid god components, unnecessary stores, duplicated types, and hard-to-maintain UI code.

## Core rule

Page components orchestrate.
Feature components render.
Shared UI is reused intentionally.

## Page components may

- Receive Inertia props.
- Compose layout.
- Pass data to feature components.
- Handle page-level breadcrumbs/head metadata.
- Keep page-level submit wiring when it is tiny.

## Page components must not

- Contain large tables inline.
- Contain large forms inline.
- Contain modal implementations inline.
- Own unrelated UI sections.
- Become god components.
- Store page-local server props in Pinia/Vuex.

## Feature components

Use feature components for:
- tables
- lists
- cards
- detail sections
- forms
- status badges
- action bars
- modals/popups

Suggested structure:

```txt
resources/js/pages/<FeaturePage>.vue
resources/js/components/<feature>/<FeatureComponent>.vue
resources/js/components/<feature>/types.ts
resources/js/components/<feature>/utils.ts
resources/js/components/<feature>/modals/<FeatureModal>.vue
```

## Types

Use `type` for:
- Inertia props
- backend DTO shapes
- union values
- composed shapes
- nullable frontend data shapes

Use `interface` for:
- object contracts intended to be extended
- public/extensible component contracts when useful

Do not duplicate inline prop types across many components.
Move feature-specific types to a feature `types.ts`.

## Stores

Do not introduce Pinia/Vuex stores for page-local server props.

Use stores only for shared application state:
- auth user
- active workshop when shared across many pages
- theme
- locale
- sidebar state
- notifications/toasts

## Modals and popups

Feature-specific modals live near the feature:
- `components/dashboard/modals/`
- `components/customers/modals/`

Shared modal primitives belong in common UI only when reused by multiple features.

## Formatting helpers

Small helper functions may stay in a component.
Move helpers to `utils.ts` when:
- reused by multiple components
- the component becomes noisy
- the helper has domain-specific formatting rules

## Review red flags

- Dashboard.vue or page component over 250-300 lines without reason
- table markup mixed with page orchestration
- duplicated type shapes
- state store used only to pass Inertia props to one page
- feature-specific modal in a global shared folder
- icon-only buttons without labels
- action buttons not reflecting backend permissions/actions
