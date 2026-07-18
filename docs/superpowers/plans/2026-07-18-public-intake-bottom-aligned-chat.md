# Public Intake Bottom-Aligned Chat Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Expand the public intake conversation to the available viewport height after the first message, keep new content at the bottom, and remove the white response-panel background.

**Architecture:** `Welcome.vue` consumes the flow's existing `expanded-change` event and owns the page-level centered-versus-expanded layout. `PublicIntakeFlow.vue` fills that expanded container, keeps only the transcript scrollable, and renders the active response wrapper without a separate surface.

**Tech Stack:** Vue 3, Inertia.js, Tailwind CSS, Vitest, Vue Test Utils

## Global Constraints

- Keep the initial intake starter centered and unchanged.
- Do not add image attachments, routes, persistence, dependencies, or business-rule changes.
- Preserve focus, validation, editing, cancellation, submission, reduced-motion, and responsive behavior.
- Do not include unrelated existing worktree changes in any commit.

---

### Task 1: State-aware public workspace height

**Files:**
- Modify: `resources/js/pages/Welcome.vue`
- Test: `resources/js/pages/Welcome.test.ts`

**Interfaces:**
- Consumes: `PublicIntakeFlow` event `expanded-change: [expanded: boolean]`
- Produces: local `intakeExpanded: Ref<boolean>` controlling the Welcome content wrapper classes

- [x] **Step 1: Write the failing page-layout test**

Add source assertions that require `Welcome.vue` to declare `intakeExpanded`, bind `@expanded-change="intakeExpanded = $event"`, and switch the wrapper between centered starter classes and expanded available-height classes. Replace the obsolete assertion that forbids `intakeExpanded`.

```ts
expect(welcomeSource).toContain('const intakeExpanded = ref(false)');
expect(welcomeSource).toContain('@expanded-change="intakeExpanded = $event"');
expect(welcomeSource).toContain("intakeExpanded ?");
expect(welcomeSource).toContain("'h-[calc(100dvh-4rem)] items-stretch py-0 lg:h-dvh'");
```

- [x] **Step 2: Run the focused test and verify RED**

Run: `npm test -- resources/js/pages/Welcome.test.ts`

Expected: FAIL because `Welcome.vue` does not declare or consume `intakeExpanded`.

- [x] **Step 3: Implement the state-aware wrapper**

Import `ref`, declare `const intakeExpanded = ref(false)`, bind the flow event, and dynamically switch the wrapper classes:

```vue
<div
    class="mx-auto flex w-full max-w-4xl px-4 sm:px-6 lg:px-8"
    :class="intakeExpanded ? 'h-[calc(100dvh-4rem)] items-stretch py-0 lg:h-dvh' : 'min-h-[calc(100dvh-4rem)] items-center py-8 lg:min-h-dvh'"
>
    <PublicIntakeFlow v-else :workshops="workshops" @expanded-change="intakeExpanded = $event" />
</div>
```

- [x] **Step 4: Run the focused test and verify GREEN**

Run: `npm test -- resources/js/pages/Welcome.test.ts`

Expected: all tests in `Welcome.test.ts` PASS.

### Task 2: Full-height transcript and transparent response footer

**Files:**
- Modify: `resources/js/components/public-intake/PublicIntakeFlow.vue`
- Test: `resources/js/components/public-intake/PublicIntakeFlow.test.ts`

**Interfaces:**
- Consumes: the available-height wrapper from Task 1
- Produces: an expanded flow section/form that fills its parent; transcript remains the only scrollable region

- [x] **Step 1: Write failing component-layout tests**

Update the existing docked-composer test to require the expanded section and workspace to fill their parent, require the transcript to retain `flex-1`, `overflow-y-auto`, and bottom alignment, reject the `max-h-[24rem]` cap, and reject the response wrapper's `border-t` and `bg-white` classes.

```ts
expect(wrapper.get('[data-testid="intake-flow"]').classes()).toContain('h-full');
expect(wrapper.get('[data-testid="intake-workspace"]').classes()).toContain('h-full');
expect(wrapper.get('[data-testid="intake-transcript"]').classes()).toContain('flex-1');
expect(wrapper.get('[data-testid="intake-transcript"]').classes()).not.toContain('max-h-[24rem]');
expect(wrapper.get('[data-testid="intake-chat"]').classes()).toContain('justify-end');
expect(wrapper.get('[data-testid="intake-composer"]').classes()).not.toContain('border-t');
expect(wrapper.get('[data-testid="intake-composer"]').classes()).not.toContain('bg-white');
```

- [x] **Step 2: Run the focused test and verify RED**

Run: `npm test -- resources/js/components/public-intake/PublicIntakeFlow.test.ts`

Expected: FAIL because the flow does not fill the parent, the transcript is capped, and the response wrapper has a white surface and top border.

- [x] **Step 3: Implement the minimal layout changes**

Add `data-testid="intake-flow"` and dynamic height classes to the root section and form, remove `max-h-[24rem]` from the transcript, and change the expanded response wrapper classes from the white bordered panel to a transparent padded footer:

```vue
<section data-testid="intake-flow" :class="chatExpanded ? 'h-full w-full min-h-0' : 'w-full'">
<form :class="['intake-workspace mx-auto flex w-full max-w-3xl flex-col', chatExpanded ? 'h-full min-h-0' : '']">
<div class="min-h-0 flex-1 overflow-y-auto overscroll-contain ...">
<div :class="chatExpanded ? 'shrink-0 px-4 py-4 sm:px-7 lg:px-9' : 'w-full'">
```

- [x] **Step 4: Run the focused component test and verify GREEN**

Run: `npm test -- resources/js/components/public-intake/PublicIntakeFlow.test.ts`

Expected: all tests in `PublicIntakeFlow.test.ts` PASS.

- [x] **Step 5: Run combined focused verification**

Run: `npm test -- resources/js/pages/Welcome.test.ts resources/js/components/public-intake/PublicIntakeFlow.test.ts`

Expected: both focused test files PASS with zero failures.

- [x] **Step 6: Check formatting and diff integrity**

Run: `npx prettier --check resources/js/pages/Welcome.vue resources/js/pages/Welcome.test.ts resources/js/components/public-intake/PublicIntakeFlow.vue resources/js/components/public-intake/PublicIntakeFlow.test.ts`

Expected: all four files use Prettier formatting.

Run: `git diff --check -- resources/js/pages/Welcome.vue resources/js/pages/Welcome.test.ts resources/js/components/public-intake/PublicIntakeFlow.vue resources/js/components/public-intake/PublicIntakeFlow.test.ts`

Expected: no whitespace errors.
