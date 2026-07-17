# Welcome Workspace Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:executing-plans` to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking. Repository rules require single-agent execution unless the user explicitly requests subagents.

**Goal:** Build a stable AI-chat-style Welcome workspace with a persistent sidebar, useful starter content, prompt helpers, smooth intake expansion, information panels, and responsive navigation.

**Architecture:** A shared `PublicWorkspaceLayout` owns navigation and overlay-panel state. `Welcome.vue` only composes the layout and page states, while `PublicIntakeFlow.vue` remains the sole owner of the Inertia form and conversation transitions.

**Tech Stack:** Vue 3, TypeScript, Inertia.js, Tailwind CSS, Radix Vue Sheet primitives, Vitest, Vue Test Utils.

## Global Constraints

- Read `AGENTS.md` and `docs/superpowers/specs/2026-07-17-public-workspace-roadmap-design.md` before editing.
- Preserve all existing uncommitted work and do not modify backend routes, persistence, or request payloads in this plan.
- Do not add a store, duplicate intake form, pricing API, dependency, or migration.
- Keep `PublicIntakeFlow.vue` as the single form owner and preserve its edit, cancellation, validation, workshop, and single-submit behavior.
- Prompt examples set the description field and focus it; they never submit automatically.
- Use a 200-250 ms motion-safe reveal and a bounded internally scrolling transcript.
- Keep staff login and workshop registration reachable without duplicating the old wide header.

---

### Task 1: Shared public workspace shell

**Files:**
- Create: `resources/js/layouts/PublicWorkspaceLayout.vue`
- Create: `resources/js/layouts/PublicWorkspaceLayout.test.ts`
- Reuse: `resources/js/components/public/PublicBrand.vue`

**Interfaces:**
- Consumes: `canLogin`, `canRegister`, `adminLoginUrl`, and `adminRegisterUrl` already provided by `PublicIntakeController`.
- Produces: a default central-content slot and named `history` sidebar slot. It owns only `activeInfoPanel` and mobile-menu state.

- [ ] **Step 1: Write the failing layout tests**

```ts
// @vitest-environment jsdom
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import PublicWorkspaceLayout from './PublicWorkspaceLayout.vue';

vi.mock('@inertiajs/vue3', () => ({
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
}));
vi.stubGlobal('route', (name: string) => `/${name}`);

const mountLayout = () => mount(PublicWorkspaceLayout, {
    props: {
        canLogin: true,
        canRegister: true,
        adminLoginUrl: 'https://admin.test/login',
        adminRegisterUrl: 'https://admin.test/register',
    },
    slots: {
        default: '<div data-testid="workspace-content">Content</div>',
        history: '<div data-testid="history-slot">History</div>',
    },
});

describe('PublicWorkspaceLayout', () => {
    it('renders stable desktop navigation and both slots', () => {
        const wrapper = mountLayout();
        const sidebar = wrapper.get('[data-testid="desktop-sidebar"]');
        expect(sidebar.text()).toContain('New request');
        expect(wrapper.text()).toContain('My requests');
        expect(wrapper.text()).toContain('How it works');
        expect(wrapper.text()).toContain('Prices');
        expect(wrapper.text()).toContain('Help');
        expect(wrapper.text()).toContain('For workshops');
        expect(wrapper.text()).toContain('Staff login');
        expect(wrapper.find('[data-testid="history-slot"]').exists()).toBe(true);
        expect(wrapper.find('[data-testid="workspace-content"]').exists()).toBe(true);
    });

    it('provides an accessible mobile menu trigger', () => {
        expect(mountLayout().get('[aria-label="Open navigation"]').exists()).toBe(true);
    });

    it.each(['How it works', 'Prices', 'Help'])('opens and closes %s without navigation', async (label) => {
        const wrapper = mountLayout();
        await wrapper.get(`button[aria-label="Open ${label}"]`).trigger('click');
        expect(wrapper.get('[role="dialog"]').text()).toContain(label);
        await wrapper.get('button[aria-label="Close information panel"]').trigger('click');
        expect(wrapper.find('[role="dialog"]').exists()).toBe(false);
    });

    it('preserves central form state while an information panel opens', async () => {
        const wrapper = mount(PublicWorkspaceLayout, {
            props: { adminLoginUrl: '', adminRegisterUrl: '' },
            slots: { default: '<input data-testid="draft" value="Brake noise" />' },
        });
        await wrapper.get('button[aria-label="Open Prices"]').trigger('click');
        await wrapper.get('button[aria-label="Close information panel"]').trigger('click');
        expect((wrapper.get('[data-testid="draft"]').element as HTMLInputElement).value).toBe('Brake noise');
    });
});
```

- [ ] **Step 2: Run the test and verify the red state**

Run: `npm test -- resources/js/layouts/PublicWorkspaceLayout.test.ts`

Expected: FAIL because `PublicWorkspaceLayout.vue` does not exist.

- [ ] **Step 3: Implement the shell with focused local state**

Start the component with this exact public API:

```vue
<script setup lang="ts">
import PublicBrand from '@/components/public/PublicBrand.vue';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

type InfoPanel = 'how-it-works' | 'prices' | 'help';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
    adminLoginUrl: string;
    adminRegisterUrl: string;
}>();

const activeInfoPanel = ref<InfoPanel | null>(null);
const mobileOpen = ref(false);

const openInfoPanel = (panel: InfoPanel) => {
    activeInfoPanel.value = panel;
    mobileOpen.value = false;
};
</script>
```

The template must:

- render a fixed-width `hidden lg:flex` aside with `data-testid="desktop-sidebar"`;
- link New request to `route('home')` and My requests to `route('customer-portal.index')`;
- render `<slot name="history" />` directly below My requests;
- provide buttons with `aria-label="Open How it works"`, `Open Prices`, and `Open Help`;
- retain staff login and workshop registration using the provided absolute URLs;
- provide a mobile Sheet trigger with `aria-label="Open navigation"`;
- render one Sheet/dialog at a time with `aria-label="Close information panel"`;
- place `<slot />` inside `<main class="min-w-0 flex-1">`;
- say in Prices that the workshop confirms diagnosis and estimate before work; and
- close mobile navigation after every action.

Reuse `resources/js/components/ui/sheet/*`. Do not mount `PublicHeader.vue` in this layout.

- [ ] **Step 4: Format and run focused validation**

```bash
npx prettier --write resources/js/layouts/PublicWorkspaceLayout.vue resources/js/layouts/PublicWorkspaceLayout.test.ts
npm test -- resources/js/layouts/PublicWorkspaceLayout.test.ts
npx eslint resources/js/layouts/PublicWorkspaceLayout.vue resources/js/layouts/PublicWorkspaceLayout.test.ts
```

Expected: tests PASS and ESLint exits 0.

- [ ] **Step 5: Commit the shell**

```bash
git add resources/js/layouts/PublicWorkspaceLayout.vue resources/js/layouts/PublicWorkspaceLayout.test.ts
git commit -m "feat(public): add workspace shell"
```

### Task 2: Useful Welcome starter and prompt helpers

**Files:**
- Modify: `resources/js/components/public-intake/PublicIntakeFlow.vue`
- Modify: `resources/js/components/public-intake/PublicIntakeFlow.test.ts`

**Interfaces:**
- Consumes: the existing `useForm<PublicIntakePayload>` and `continueConversation()`.
- Produces: `selectProblemPrompt(prompt: string): Promise<void>` and starter markup inside the same form.

- [ ] **Step 1: Add a failing starter-content test**

```ts
it('shows useful starter content and fills without sending a prompt example', async () => {
    const wrapper = mount(PublicIntakeFlow, { props: { workshops: [{ id: 1, name: 'Main Auto' }] } });

    expect(wrapper.get('[data-testid="intake-intro"]').text()).toContain('What is happening with your car?');
    expect(wrapper.text()).toContain('The car will not start');

    await buttonWithText(wrapper, 'The car will not start')?.trigger('click');

    expect((wrapper.get('#message').element as HTMLTextAreaElement).value).toBe('The car will not start');
    expect(document.activeElement).toBe(wrapper.get('#message').element);
    expect(wrapper.find('[data-testid="intake-chat"]').exists()).toBe(false);
    expect(inertia.post).not.toHaveBeenCalled();
});
```

- [ ] **Step 2: Run the focused test and verify failure**

Run: `npm test -- resources/js/components/public-intake/PublicIntakeFlow.test.ts -t "starter content"`

Expected: FAIL because the intro and prompt buttons do not exist.

- [ ] **Step 3: Implement prompt filling without a second form**

Add to the existing script:

```ts
const problemPrompts = ['The car will not start', 'I hear an unusual noise', 'I need scheduled maintenance'] as const;

const selectProblemPrompt = async (prompt: string) => {
    form.message = prompt;
    form.clearErrors('message');
    await nextTick();
    document.getElementById('message')?.focus();
};
```

Inside the existing form, render `data-testid="intake-intro"` only while `!chatExpanded`. Include the agreed heading, workshop-confirmation copy, and three `type="button"` controls rendered from `problemPrompts`. Each button calls `selectProblemPrompt(prompt)`. Keep the existing textarea, honeypot, maxlength, Enter, Shift+Enter, and Send behavior unchanged.

- [ ] **Step 4: Run the complete intake-flow validation**

```bash
npx prettier --write resources/js/components/public-intake/PublicIntakeFlow.vue resources/js/components/public-intake/PublicIntakeFlow.test.ts
npm test -- resources/js/components/public-intake/PublicIntakeFlow.test.ts
npx eslint resources/js/components/public-intake/PublicIntakeFlow.vue resources/js/components/public-intake/PublicIntakeFlow.test.ts
```

Expected: all focused tests PASS and ESLint exits 0.

- [ ] **Step 5: Commit the starter**

```bash
git add resources/js/components/public-intake/PublicIntakeFlow.vue resources/js/components/public-intake/PublicIntakeFlow.test.ts
git commit -m "feat(intake): add guided starter"
```

### Task 3: Stable smooth conversation reveal

**Files:**
- Modify: `resources/js/components/public-intake/PublicIntakeFlow.vue`
- Modify: `resources/js/components/public-intake/PublicIntakeFlow.test.ts`
- Modify: `resources/css/app.css`

**Interfaces:**
- Consumes: existing `chatExpanded`, `transcriptViewport`, and `scrollTranscriptToEnd()`.
- Produces: stable `data-testid="intake-workspace"` markup and motion-safe intro/transcript transitions.

- [ ] **Step 1: Add failing structure and motion tests**

```ts
it('keeps one bounded workspace shell before and after expansion', async () => {
    const wrapper = mount(PublicIntakeFlow, { props: { workshops: [{ id: 1, name: 'Main Auto' }] } });
    const shell = wrapper.get('[data-testid="intake-workspace"]');

    await wrapper.get('#message').setValue('Brake noise');
    await buttonWithText(wrapper, 'Send')?.trigger('click');

    expect(wrapper.get('[data-testid="intake-workspace"]').element).toBe(shell.element);
    expect(wrapper.get('[data-testid="intake-transcript"]').classes()).toContain('overflow-y-auto');
    expect(wrapper.get('[data-testid="intake-composer"]').classes()).toContain('shrink-0');
});
```

Add a source assertion that `resources/css/app.css` contains `.intake-reveal-enter-active` and `@media (prefers-reduced-motion: reduce)`.

- [ ] **Step 2: Run the focused test and verify failure**

Run: `npm test -- resources/js/components/public-intake/PublicIntakeFlow.test.ts -t "bounded workspace"`

Expected: FAIL because the stable shell and transition classes do not exist.

- [ ] **Step 3: Replace the abrupt full-card swap**

Keep one form/root element with `data-testid="intake-workspace"` and class `intake-workspace`. Remove the conditional `public-card h-[calc(100dvh-7rem)] max-h-[48rem] min-h-[32rem]` class swap. Transition the intro and conversation above the same composer:

```vue
<Transition name="intake-reveal" mode="out-in">
    <section v-if="!chatExpanded" key="starter" data-testid="intake-intro" aria-labelledby="intake-starter-title">
        <h2 id="intake-starter-title">What is happening with your car?</h2>
        <p>Describe the issue in your own words. The workshop will confirm the details with you.</p>
        <button v-for="prompt in problemPrompts" :key="prompt" type="button" @click="selectProblemPrompt(prompt)">
            {{ prompt }}
        </button>
    </section>
    <div
        v-else
        key="conversation"
        ref="transcriptViewport"
        data-testid="intake-transcript"
        class="max-h-[24rem] min-h-0 flex-1 overflow-y-auto overscroll-contain"
    >
        <PublicIntakeTranscript
            :completed="completedConversation"
            :active-state="activeState"
            :message="form.message"
            :phone="form.phone"
            :customer-name="form.customer_name"
            :vehicle-summary="vehicleSummary"
            :selected-workshop-name="selectedWorkshop?.name"
            :editing-answer="editContext?.answer"
            @edit="editAnswer"
        />
    </div>
</Transition>
```

Keep the complete existing `<div id="active-response">` composer immediately after the Transition, inside the same form. Preserve every existing active-control branch and handler.

Add exact motion rules:

```css
.intake-reveal-enter-active,
.intake-reveal-leave-active {
    transition: opacity 240ms ease, transform 240ms ease;
}
.intake-reveal-enter-from,
.intake-reveal-leave-to {
    opacity: 0;
    transform: translateY(8px);
}
@media (prefers-reduced-motion: reduce) {
    .intake-reveal-enter-active,
    .intake-reveal-leave-active {
        transition: none;
    }
}
```

- [ ] **Step 4: Run intake regression validation**

```bash
npx prettier --write resources/js/components/public-intake/PublicIntakeFlow.vue resources/js/components/public-intake/PublicIntakeFlow.test.ts resources/css/app.css
npm test -- resources/js/components/public-intake/PublicIntakeFlow.test.ts resources/js/components/public-intake/state.test.ts
npx eslint resources/js/components/public-intake/PublicIntakeFlow.vue resources/js/components/public-intake/PublicIntakeFlow.test.ts
```

Expected: all focused tests PASS and ESLint exits 0.

- [ ] **Step 5: Commit the transition**

```bash
git add resources/js/components/public-intake/PublicIntakeFlow.vue resources/js/components/public-intake/PublicIntakeFlow.test.ts resources/css/app.css
git commit -m "fix(intake): smooth chat expansion"
```

### Task 4: Compose Welcome with the shared shell

**Files:**
- Modify: `resources/js/pages/Welcome.vue`
- Modify: `resources/js/pages/Welcome.test.ts`
- Modify if stale: `resources/js/pages/PublicExperienceDesign.test.ts`
- Delete only when unused: `resources/js/components/public/PublicHeader.vue`

**Interfaces:**
- Consumes: `PublicWorkspaceLayout` props and existing intake/success components.
- Produces: one stable Welcome composition without an `intakeExpanded` page-layout switch.

- [ ] **Step 1: Replace old layout assertions with failing workspace assertions**

```ts
expect(welcomeSource).toContain('PublicWorkspaceLayout');
expect(welcomeSource).not.toContain('PublicHeader');
expect(welcomeSource).not.toContain('PublicTrustPanel');
expect(welcomeSource).not.toContain('intakeExpanded');
expect(welcomeSource).not.toContain("lg:grid-cols-[minmax(0,1fr)_18rem]");
```

Keep the existing success-state behavior assertions.

- [ ] **Step 2: Run the Welcome test and verify failure**

Run: `npm test -- resources/js/pages/Welcome.test.ts`

Expected: FAIL because Welcome still uses the header, trust panel, and expansion grid.

- [ ] **Step 3: Implement the stable page composition**

Use this structure:

```vue
<PublicWorkspaceLayout
    :can-login="canLogin"
    :can-register="canRegister"
    :admin-login-url="adminLoginUrl"
    :admin-register-url="adminRegisterUrl"
>
    <div class="mx-auto flex min-h-dvh w-full max-w-4xl items-center px-4 py-8 sm:px-6 lg:min-h-screen lg:px-8">
        <PublicIntakeSuccess v-if="intakeSubmitted" :workshop-name="intakeWorkshopName" />
        <PublicIntakeFlow v-else :workshops="workshops" />
    </div>
</PublicWorkspaceLayout>
```

Remove the `expanded-change` page-layout listener. Delete `PublicHeader.vue` only after `rg -n "PublicHeader" resources/js` shows no production imports; otherwise keep it and report the remaining consumer.

- [ ] **Step 4: Run Welcome and public experience validation**

```bash
npx prettier --write resources/js/pages/Welcome.vue resources/js/pages/Welcome.test.ts resources/js/pages/PublicExperienceDesign.test.ts
npm test -- resources/js/pages/Welcome.test.ts resources/js/pages/PublicExperienceDesign.test.ts resources/js/components/public-intake/PublicIntakeFlow.test.ts
npx eslint resources/js/pages/Welcome.vue resources/js/pages/Welcome.test.ts resources/js/layouts/PublicWorkspaceLayout.vue
```

Expected: all focused tests PASS. Update only stale source-shape assertions; do not weaken behavior assertions.

- [ ] **Step 5: Commit Welcome integration**

```bash
git add resources/js/pages/Welcome.vue resources/js/pages/Welcome.test.ts resources/js/pages/PublicExperienceDesign.test.ts resources/js/components/public/PublicHeader.vue
git commit -m "feat(public): compose welcome workspace"
```

If `PublicHeader.vue` remains in use, omit it from `git add`.

### Task 5: Milestone 1 verification and product checkpoint

**Files:**
- Modify only if a focused verification failure identifies a scoped defect.

**Interfaces:**
- Consumes: completed Tasks 1-4.
- Produces: evidence that Welcome is ready for user acceptance; it does not authorize history work.

- [ ] **Step 1: Run the focused frontend suite**

```bash
npm test -- resources/js/layouts/PublicWorkspaceLayout.test.ts resources/js/pages/Welcome.test.ts resources/js/pages/PublicExperienceDesign.test.ts resources/js/components/public-intake/PublicIntakeFlow.test.ts resources/js/components/public-intake/state.test.ts resources/js/components/public-intake/PublicIntakeTranscript.test.ts
```

Expected: all listed test files PASS.

- [ ] **Step 2: Run scoped static checks**

```bash
npx prettier --check resources/js/layouts/PublicWorkspaceLayout.vue resources/js/layouts/PublicWorkspaceLayout.test.ts resources/js/pages/Welcome.vue resources/js/pages/Welcome.test.ts resources/js/components/public-intake/PublicIntakeFlow.vue resources/js/components/public-intake/PublicIntakeFlow.test.ts resources/css/app.css
npx eslint resources/js/layouts/PublicWorkspaceLayout.vue resources/js/layouts/PublicWorkspaceLayout.test.ts resources/js/pages/Welcome.vue resources/js/pages/Welcome.test.ts resources/js/components/public-intake/PublicIntakeFlow.vue resources/js/components/public-intake/PublicIntakeFlow.test.ts
git diff --check
```

Expected: every command exits 0.

- [ ] **Step 3: Run the focused backend regression**

Run: `php artisan test tests/Feature/PublicIntakeSubmissionTest.php`

Expected: PASS, confirming the submission contract is unchanged.

- [ ] **Step 4: Perform desktop and mobile visual checks**

With an already-running approved local app, verify at approximately 1440 px and 390 px widths:

- starter heading, copy, examples, and composer are visible;
- sidebar is persistent on desktop and a menu on mobile;
- examples fill but do not send;
- first Send reveals the transcript without a white full-page flash;
- composer remains reachable and transcript scrolls internally;
- How it works, Prices, and Help preserve typed text; and
- reduced-motion mode removes animated movement.

Do not start a service or Docker without user approval. Record screenshots and defects.

- [ ] **Step 5: Stop for explicit user acceptance**

Report files changed, commands/results, screenshots, and unresolved issues. Do not execute `2026-07-17-customer-request-history.md` until the user explicitly accepts Milestone 1.
