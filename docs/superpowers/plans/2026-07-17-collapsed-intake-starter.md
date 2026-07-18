# Collapsed Intake Starter Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Show a compact centered description composer on first load and expand the existing bounded intake chat only after a valid first submission.

**Architecture:** `PublicIntakeFlow.vue` keeps sole ownership of the existing Inertia form and derives its collapsed/expanded render mode from durable conversation state. It emits `expanded-change` so `Welcome.vue` can switch page layout and trust-panel visibility without duplicating form state or introducing a store.

**Tech Stack:** Laravel 12, Inertia 2, Vue 3 Composition API, TypeScript, Tailwind CSS, Vitest, Vue Test Utils, jsdom.

## Global Constraints

- Keep one `useForm<PublicIntakePayload>` instance and the existing final POST contract.
- Do not change routes, backend validation, persistence, workshop rules, or database schema.
- Collapsed mode is exactly `conversationState === 'problem' && editContext === null`.
- Focus or typing must not expand the chat; only a valid first submission may advance to `phone` and expand it.
- Enter submits only in collapsed mode; Shift+Enter and Enter during a later problem edit preserve normal textarea input.
- A confirmed cancellation resets the form and returns the page to collapsed mode.
- Preserve the expanded chat's internal scrolling, edit-return behavior, optional details, validation, workshop selection, and single final POST.
- Work as a single agent; do not dispatch subagents for this repository.
- The target homepage/intake files belong to an existing uncommitted rewrite; do not stage or commit implementation files unless the user explicitly requests a combined commit after review.

---

### Task 1: Specify collapsed and expanded flow behavior

**Files:**
- Modify: `resources/js/components/public-intake/PublicIntakeFlow.test.ts`

**Interfaces:**
- Consumes: existing `PublicIntakeFlow` props `{ workshops: WorkshopOption[] }`.
- Produces: rendered behavior contract for `data-testid="intake-starter"`, `data-testid="intake-chat"`, and the `expanded-change` event carrying `[expanded: boolean]`.

- [ ] **Step 1: Add failing tests for the compact initial mode**

Add tests that mount the flow with one workshop and assert the compact mode is the only intake surface before submission:

```ts
it('starts with only the compact problem composer', () => {
    const wrapper = mount(PublicIntakeFlow, {
        props: { workshops: [{ id: 1, name: 'Main Auto' }] },
    });

    expect(wrapper.find('[data-testid="intake-starter"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="intake-chat"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="intake-transcript"]').exists()).toBe(false);
    expect(wrapper.find('#message').exists()).toBe(true);
    expect(buttonWithText(wrapper, 'Send')).toBeDefined();
});

it('does not expand on focus or typing', async () => {
    const wrapper = mount(PublicIntakeFlow, {
        props: { workshops: [{ id: 1, name: 'Main Auto' }] },
    });

    await wrapper.get('#message').trigger('focus');
    await wrapper.get('#message').setValue('Brake noise');

    expect(wrapper.find('[data-testid="intake-starter"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="intake-chat"]').exists()).toBe(false);
    expect(wrapper.emitted('expanded-change')).toBeUndefined();
});
```

- [ ] **Step 2: Add failing tests for validation and both send mechanisms**

```ts
it('keeps an empty submission collapsed with an accessible error', async () => {
    const wrapper = mount(PublicIntakeFlow, { props: { workshops: [] } });

    await buttonWithText(wrapper, 'Send')?.trigger('click');

    expect(wrapper.find('[data-testid="intake-starter"]').exists()).toBe(true);
    expect(wrapper.get('#message').attributes('aria-invalid')).toBe('true');
    expect(wrapper.get('#message-error').text()).toContain('Tell us what is happening');
    expect(wrapper.emitted('expanded-change')).toBeUndefined();
});

it.each(['button', 'enter'] as const)('expands after a valid first submission by %s', async (method) => {
    const wrapper = mount(PublicIntakeFlow, {
        props: { workshops: [{ id: 1, name: 'Main Auto' }] },
    });
    await wrapper.get('#message').setValue('Brake noise');

    if (method === 'button') {
        await buttonWithText(wrapper, 'Send')?.trigger('click');
    } else {
        await wrapper.get('#message').trigger('keydown', { key: 'Enter' });
    }

    expect(wrapper.find('[data-testid="intake-starter"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="intake-chat"]').exists()).toBe(true);
    expect(wrapper.get('[data-testid="intake-transcript"]').text()).toContain('Brake noise');
    expect(wrapper.find('#phone').exists()).toBe(true);
    expect(wrapper.text()).not.toContain('Would you like to add anything else?');
    expect(wrapper.emitted('expanded-change')).toEqual([[true]]);
});

it('keeps Shift+Enter in the compact textarea without expanding', async () => {
    const wrapper = mount(PublicIntakeFlow, { props: { workshops: [] } });
    await wrapper.get('#message').setValue('Brake noise');
    const shiftEnter = new KeyboardEvent('keydown', {
        key: 'Enter',
        shiftKey: true,
        bubbles: true,
        cancelable: true,
    });

    wrapper.get('#message').element.dispatchEvent(shiftEnter);
    await wrapper.vm.$nextTick();

    expect(shiftEnter.defaultPrevented).toBe(false);
    expect(wrapper.find('[data-testid="intake-starter"]').exists()).toBe(true);
    expect(wrapper.emitted('expanded-change')).toBeUndefined();
});
```

- [ ] **Step 3: Extend editing and cancellation regression coverage**

In the existing description-edit test, assert `data-testid="intake-chat"` remains present after saving. In the existing confirmed-cancellation test, assert the chat disappears, the starter returns, and emitted expansion events end with `[false]`:

```ts
expect(wrapper.find('[data-testid="intake-chat"]').exists()).toBe(true);

const expansionEvents = wrapper.emitted('expanded-change');
expect(expansionEvents).toEqual([[true], [false]]);
expect(wrapper.find('[data-testid="intake-starter"]').exists()).toBe(true);
expect(wrapper.find('[data-testid="intake-chat"]').exists()).toBe(false);
```

- [ ] **Step 4: Run the focused test and verify the new contract fails**

Run:

```bash
npm test -- resources/js/components/public-intake/PublicIntakeFlow.test.ts
```

Expected: FAIL because `intake-starter`, the `expanded-change` event, and compact Enter handling do not exist yet.

- [ ] **Step 5: Record a no-staging review checkpoint**

```bash
git status --short resources/js/components/public-intake/PublicIntakeFlow.test.ts
```

Expected: only the intended existing feature path is reported. Do not stage it because it contains prior uncommitted homepage work.

---

### Task 2: Implement the compact starter inside the existing flow

**Files:**
- Modify: `resources/js/components/public-intake/PublicIntakeFlow.vue`
- Test: `resources/js/components/public-intake/PublicIntakeFlow.test.ts`

**Interfaces:**
- Consumes: existing durable `conversationState`, temporary `editContext`, `continueConversation()`, and `PublicIntakePayload.message`.
- Produces: Vue event `expanded-change: [expanded: boolean]`; computed `chatExpanded: boolean`; compact starter and expanded chat render modes sharing one form.

- [ ] **Step 1: Add the explicit expansion interface**

Import `watch`, define the event, derive mode only from durable progress plus edit context, and emit only when mode changes:

```ts
import { computed, nextTick, ref, watch } from 'vue';

const emit = defineEmits<{
    'expanded-change': [expanded: boolean];
}>();

const chatExpanded = computed(() => conversationState.value !== 'problem' || editContext.value !== null);

watch(chatExpanded, (expanded) => {
    emit('expanded-change', expanded);
});
```

Do not emit on initial mount: `Welcome.vue` initializes its layout to collapsed without needing an initial event.

- [ ] **Step 2: Add keyboard handling limited to the collapsed starter**

Add a handler beside `continueConversation()`:

```ts
const handleProblemEnter = (event: KeyboardEvent) => {
    if (chatExpanded.value || event.shiftKey) return;

    event.preventDefault();
    void continueConversation();
};
```

Bind it to the problem textarea with `@keydown.enter="handleProblemEnter"`. This makes Enter submit only before expansion; Shift+Enter and later problem edits retain normal textarea behavior.

- [ ] **Step 3: Make the single form render as starter or chat shell**

Keep the honeypot and `@submit.prevent="submit"` on the same form. Replace the fixed form test id/class with derived values:

```vue
<form
    :data-testid="chatExpanded ? 'intake-chat' : 'intake-starter'"
    :class="
        chatExpanded
            ? 'public-card flex h-[calc(100dvh-7rem)] max-h-[48rem] min-h-[32rem] flex-col overflow-hidden'
            : 'mx-auto w-full max-w-3xl'
    "
    @submit.prevent="submit"
>
```

Conditionally render the transcript only while expanded:

```vue
<div
    v-if="chatExpanded"
    ref="transcriptViewport"
    data-testid="intake-transcript"
    class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-4 py-5 sm:px-7 sm:py-7 lg:px-9"
>
    <!-- existing transcript content unchanged -->
</div>
```

Keep the existing active-response region, but derive its styling and test id so the collapsed mode is a compact surface rather than the bottom section of a large card:

```vue
<div
    id="active-response"
    :data-testid="chatExpanded ? 'intake-composer' : 'intake-starter-composer'"
    :class="
        chatExpanded
            ? 'shrink-0 border-t border-slate-200 bg-white px-4 py-4 sm:px-7 lg:px-9'
            : 'w-full'
    "
>
```

- [ ] **Step 4: Adapt the existing problem composer for compact mode**

Reuse the same `v-model="form.message"` textarea and validation. Derive row count and visual treatment without duplicating form controls:

```vue
<div
    v-if="activeControl === 'problem-composer'"
    class="rounded-[1.75rem] border border-slate-200 bg-white p-2 shadow-[0_18px_48px_-24px_rgba(15,23,42,0.5)]"
>
    <label for="message" class="sr-only">Describe your car problem</label>
    <textarea
        id="message"
        v-model="form.message"
        name="message"
        :rows="chatExpanded ? 3 : 1"
        maxlength="5000"
        autofocus
        class="w-full resize-none rounded-2xl border-0 bg-slate-50 px-4 py-3 text-base leading-6 text-slate-900 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0e7c86]/25"
        placeholder="Describe the issue..."
        :aria-invalid="Boolean(form.errors.message)"
        aria-describedby="message-error"
        @input="form.clearErrors('message')"
        @keydown.enter="handleProblemEnter"
    />
    <div class="flex items-center justify-between gap-3 px-2 pb-1 pt-2">
        <p v-if="form.errors.message" id="message-error" class="text-sm font-medium text-red-700">
            {{ form.errors.message }}
        </p>
        <span v-else class="text-xs text-slate-500">The workshop will confirm the diagnosis.</span>
        <button type="button" class="public-button-primary ml-auto min-h-11" @click="continueConversation">
            {{ editingState ? 'Save changes' : 'Send' }}
            <Send class="size-4" aria-hidden="true" />
        </button>
    </div>
</div>
```

Do not change any other active composer. The problem composer is only visible collapsed initially or expanded during a later problem edit.

- [ ] **Step 5: Run the flow test and verify all behavior passes**

Run:

```bash
npm test -- resources/js/components/public-intake/PublicIntakeFlow.test.ts
```

Expected: all flow tests PASS, including existing edit, scrolling, optional-detail, submission, cancellation, and workshop cases.

- [ ] **Step 6: Record a no-staging flow checkpoint**

```bash
git status --short resources/js/components/public-intake/PublicIntakeFlow.vue resources/js/components/public-intake/PublicIntakeFlow.test.ts
```

Expected: only the intended flow and test paths are reported. Leave them unstaged.

---

### Task 3: Switch the homepage layout when the chat expands

**Files:**
- Modify: `resources/js/pages/Welcome.vue`
- Modify: `resources/js/pages/Welcome.test.ts`

**Interfaces:**
- Consumes: `PublicIntakeFlow` event `expanded-change: [expanded: boolean]`.
- Produces: page-local `intakeExpanded: Ref<boolean>` controlling centered layout and `PublicTrustPanel` visibility.

- [ ] **Step 1: Add rendered page-layout coverage**

Change `Welcome.test.ts` to use the jsdom environment in addition to its stable source-contract assertions. Import `mount` and `defineComponent`, then add a test with focused child stubs:

```ts
// @vitest-environment jsdom

import { mount } from '@vue/test-utils';
import { defineComponent } from 'vue';
import Welcome from './Welcome.vue';

const IntakeFlowStub = defineComponent({
    emits: ['expanded-change'],
    template: '<button data-testid="expand-intake" @click="$emit(\'expanded-change\', true)">Expand</button>',
});

it('centers the starter and reveals the trust panel only after expansion', async () => {
    const wrapper = mount(Welcome, {
        props: {
            workshops: [],
            adminLoginUrl: 'http://admin.autoservice.test:8080/login',
            adminRegisterUrl: 'http://admin.autoservice.test:8080/register',
        },
        global: {
            stubs: {
                Head: true,
                PublicHeader: true,
                PublicIntakeFlow: IntakeFlowStub,
                PublicIntakeSuccess: true,
                PublicTrustPanel: { template: '<aside data-testid="trust-panel" />' },
            },
        },
    });

    expect(wrapper.get('[data-testid="intake-layout"]').classes()).toContain('items-center');
    expect(wrapper.find('[data-testid="trust-panel"]').exists()).toBe(false);

    await wrapper.get('[data-testid="expand-intake"]').trigger('click');

    expect(wrapper.get('[data-testid="intake-layout"]').classes()).toContain('grid');
    expect(wrapper.find('[data-testid="trust-panel"]').exists()).toBe(true);
});
```

Retain the existing source assertions for header destinations, public design tokens, and success copy. Update the old assertion that always expects the desktop grid so it is covered by the rendered state change instead.

- [ ] **Step 2: Run the homepage test and verify it fails**

Run:

```bash
npm test -- resources/js/pages/Welcome.test.ts
```

Expected: FAIL because `Welcome.vue` does not yet listen for expansion or hide the trust panel.

- [ ] **Step 3: Implement page-local layout coordination**

Import `ref` and add the local state:

```ts
import { ref } from 'vue';

const intakeExpanded = ref(false);
```

Replace the existing always-expanded grid with a dynamic layout that preserves its expanded classes exactly:

```vue
<div
    v-else
    data-testid="intake-layout"
    class="mx-auto w-full max-w-7xl px-3 py-4 sm:px-6 sm:py-7 lg:px-8 lg:py-8"
    :class="
        intakeExpanded
            ? 'grid gap-5 lg:grid-cols-[minmax(0,1fr)_18rem] lg:gap-6'
            : 'flex min-h-[calc(100dvh-8.75rem)] items-center justify-center'
    "
>
    <div :class="intakeExpanded ? 'min-w-0' : 'w-full max-w-3xl'">
        <PublicIntakeFlow :workshops="workshops" @expanded-change="intakeExpanded = $event" />
    </div>
    <PublicTrustPanel v-if="intakeExpanded" />
</div>
```

Do not persist `intakeExpanded`; a page reload intentionally returns to the compact starter unless the server renders the existing success state.

- [ ] **Step 4: Run page and flow tests together**

Run:

```bash
npm test -- resources/js/pages/Welcome.test.ts resources/js/components/public-intake/PublicIntakeFlow.test.ts
```

Expected: both files PASS. The page test proves trust-panel/layout coordination; the flow test proves conversation behavior.

- [ ] **Step 5: Record a no-staging homepage checkpoint**

```bash
git status --short resources/js/pages/Welcome.vue resources/js/pages/Welcome.test.ts
```

Expected: only the intended homepage paths are reported. Leave them unstaged.

---

### Task 4: Focused regression and quality verification

**Files:**
- Verify only; modify a listed feature file only if a check exposes a regression.

**Interfaces:**
- Consumes: completed collapsed-starter and homepage-layout behavior.
- Produces: fresh evidence that the frontend contract is green and formatted.

- [ ] **Step 1: Run the focused public frontend suite**

```bash
npm test -- \
  resources/js/components/public-intake/PublicIntakeFlow.test.ts \
  resources/js/components/public-intake/state.test.ts \
  resources/js/components/public/PublicHeader.test.ts \
  resources/js/pages/PublicExperienceDesign.test.ts \
  resources/js/pages/Welcome.test.ts \
  resources/js/pages/CustomerPortal.test.ts \
  resources/js/pages/AuthPublicDesign.test.ts
```

Expected: all tests PASS with zero failures.

- [ ] **Step 2: Run scoped ESLint without write mode**

```bash
npx eslint \
  resources/js/components/public-intake/PublicIntakeFlow.vue \
  resources/js/components/public-intake/PublicIntakeFlow.test.ts \
  resources/js/pages/Welcome.vue \
  resources/js/pages/Welcome.test.ts
```

Expected: exit code 0 and no output.

- [ ] **Step 3: Run scoped formatting and whitespace checks**

```bash
npx prettier --check \
  resources/js/components/public-intake/PublicIntakeFlow.vue \
  resources/js/components/public-intake/PublicIntakeFlow.test.ts \
  resources/js/pages/Welcome.vue \
  resources/js/pages/Welcome.test.ts
git diff --check
```

Expected: Prettier reports all matched files use its style; `git diff --check` exits 0.

- [ ] **Step 4: Perform a focused manual browser check when the local preview is reachable**

At desktop and mobile widths, verify:

1. Fresh load shows the centered compact field and no trust panel.
2. Focus and typing keep it compact.
3. Shift+Enter permits multiline text.
4. Send expands to the phone step and reveals the trust panel at desktop width.
5. The expanded composer remains docked and transcript scroll stays internal.
6. Editing the problem stays expanded.
7. Confirmed cancellation returns to the compact starter.

Do not start Docker, PostgreSQL, or the dev server without user approval. If the preview is unavailable, report browser verification as blocked rather than claiming it passed.

- [ ] **Step 5: Review the final diff for scope**

Confirm no backend, route, schema, payload, trust copy, or unrelated public-page files changed. Review the paths without staging them:

```bash
git status --short resources/js/components/public-intake/PublicIntakeFlow.vue resources/js/components/public-intake/PublicIntakeFlow.test.ts resources/js/pages/Welcome.vue resources/js/pages/Welcome.test.ts
git diff --check
```

Expected: only intended feature paths are present and the whitespace check exits 0. Leave all implementation changes unstaged for user review.
