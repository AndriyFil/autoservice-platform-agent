# Public Intake Chat Repair Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Repair the homepage intake regression and make the flow behave like a bounded, bottom-anchored AI chat while resolving the four review findings.

**Architecture:** Keep one Inertia form and one final POST in `PublicIntakeFlow.vue`. Separate durable conversation progress from temporary editing through an explicit edit context, render completed exchanges through a presentational transcript component, and keep the active composer docked below an internally scrolling transcript. Preserve all Laravel routes, payloads, workshop scoping, and booking-request behavior.

**Tech Stack:** Laravel, Inertia.js, Vue 3, TypeScript, Tailwind CSS 3, Vitest, Vue Test Utils, jsdom.

## Global Constraints

- Keep `/` as the only global public intake entry and `POST /intake` as the only submission endpoint.
- Create a `BookingRequest` only on final submission and always for the server-resolved workshop.
- Keep one `useForm<PublicIntakePayload>()` instance and exactly one `form.post()` call.
- Do not change backend payloads, routes, database schema, dashboard design, customer portal, authentication behavior, or trust-panel copy.
- Keep the approved ivory, navy, petrol, and white public visual system.
- Do not add a frontend store or persistence for unfinished drafts.
- Respect `prefers-reduced-motion` when scrolling the transcript.
- Obtain explicit approval before installing `@vue/test-utils` or `jsdom`.
- Preserve all unrelated working-tree changes and stage only files belonging to the current task.

---

## File Map

- Create `resources/js/components/public-intake/PublicIntakeTranscript.vue`: render completed prompts/answers and emit edit requests; no form mutation.
- Create `resources/js/components/public-intake/PublicIntakeFlow.test.ts`: rendered interaction regressions for editing, optional details, errors, scrolling, cancellation, and final submission.
- Create `resources/js/components/public/PublicHeader.test.ts`: distinguish same-origin Inertia links from cross-origin browser anchors.
- Modify `resources/js/components/public-intake/state.ts`: explicit edit context and completed-exchange projection.
- Modify `resources/js/components/public-intake/state.test.ts`: pure transition and visibility regression coverage.
- Modify `resources/js/components/public-intake/PublicIntakeFlow.vue`: chat viewport, composer dock, edit orchestration, optional details, and validation.
- Modify `resources/js/components/public/PublicHeader.vue`: normal anchors for admin destinations.
- Modify `resources/js/pages/Welcome.test.ts`: retain visual-contract assertions only; remove claims of behavioral coverage.
- Modify `package.json` and `package-lock.json`: add the approved component-test dependencies.

---

### Task 1: Add Rendered Vue Test Infrastructure

**Files:**
- Modify: `package.json`
- Modify: `package-lock.json`
- Create: `resources/js/components/public-intake/PublicIntakeFlow.test.ts`

**Interfaces:**
- Consumes: existing Vite Vue plugin and Vitest command.
- Produces: jsdom-backed Vue mounting through `@vue/test-utils`.

- [ ] **Step 1: Request dependency approval**

Ask permission to install exactly these development dependencies:

```text
@vue/test-utils
jsdom
```

Do not continue with the install until approval is granted.

- [ ] **Step 2: Install the approved test dependencies**

Run:

```bash
npm install --save-dev @vue/test-utils jsdom
```

Expected: `package.json` and `package-lock.json` add only the requested testing packages and their transitive dependencies.

- [ ] **Step 3: Create a failing rendered regression test**

Create `PublicIntakeFlow.test.ts` with a jsdom environment, a minimal Inertia form mock, a `route` stub, and the exact reported interaction:

```ts
// @vitest-environment jsdom

import { mount } from '@vue/test-utils';
import { reactive } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import PublicIntakeFlow from './PublicIntakeFlow.vue';

const post = vi.fn();

vi.mock('@inertiajs/vue3', () => ({
    useForm: <T extends Record<string, unknown>>(defaults: T) => {
        const initial = structuredClone(defaults);
        const form = reactive({
            ...structuredClone(defaults),
            errors: {} as Record<string, string>,
            processing: false,
            data: () => Object.fromEntries(
                Object.keys(initial).map((key) => [key, structuredClone(form[key as keyof typeof form])]),
            ) as T,
            setError: (field: string, message: string) => {
                form.errors[field] = message;
            },
            clearErrors: (...fields: string[]) => {
                if (fields.length === 0) form.errors = {};
                else fields.forEach((field) => delete form.errors[field]);
            },
            reset: () => Object.assign(form, structuredClone(initial)),
            post,
        });

        return form;
    },
}));

beforeEach(() => {
    post.mockReset();
    vi.stubGlobal('route', vi.fn((name: string) => `/${name}`));
    HTMLElement.prototype.scrollTo = vi.fn();
    Element.prototype.scrollIntoView = vi.fn();
});

describe('PublicIntakeFlow', () => {
    it('returns to phone after editing the description from the phone question', async () => {
        const wrapper = mount(PublicIntakeFlow, {
            props: { workshops: [{ id: 1, name: 'Main Auto' }] },
        });

        await wrapper.get('#message').setValue('Brake noise');
        await wrapper.get('[data-testid="continue-problem"]').trigger('click');
        await wrapper.get('[aria-label="Edit problem description"]').trigger('click');
        await wrapper.get('#message').setValue('Brake noise when stopping');
        await wrapper.get('[data-testid="continue-problem"]').trigger('click');

        expect(wrapper.find('#phone').exists()).toBe(true);
        expect(wrapper.text()).not.toContain('Would you like to add anything else?');
        expect(wrapper.text()).not.toContain('Where should we send your request?');
        expect(wrapper.text()).not.toContain('Ready to send your request');
    });
});
```

- [ ] **Step 4: Run the regression test and verify RED**

Run:

```bash
npm test -- resources/js/components/public-intake/PublicIntakeFlow.test.ts
```

Expected: FAIL because the current edit saves directly to confirmation and the stable test hooks do not exist yet.

- [ ] **Step 5: Commit the test infrastructure**

```bash
git add package.json package-lock.json resources/js/components/public-intake/PublicIntakeFlow.test.ts
git commit -m "test(intake): add rendered flow coverage"
```

---

### Task 2: Separate Conversation Progress from Editing

**Files:**
- Modify: `resources/js/components/public-intake/state.ts`
- Modify: `resources/js/components/public-intake/state.test.ts`
- Modify: `resources/js/components/public-intake/PublicIntakeFlow.vue`

**Interfaces:**
- Produces: `EditContext = { answer: EditableAnswer; returnState: ConversationState }`.
- Produces: `beginEdit(answer, returnState): EditContext`.
- Produces: `activeStateFor(conversationState, editContext): ConversationState`.
- Produces: `returnStateFor(editContext): ConversationState`.
- Produces: `CompletedConversation` flags used by the transcript.

- [ ] **Step 1: Replace the old boolean-edit tests with failing context tests**

In `state.test.ts`, remove the expectations that pass `true` to `advanceConversation()` and add:

```ts
it('keeps the state that was active when an earlier answer is edited', () => {
    const edit = state?.beginEdit('problem', 'phone');

    expect(edit).toEqual({ answer: 'problem', returnState: 'phone' });
    expect(state?.activeStateFor('phone', edit ?? null)).toBe('problem');
    expect(state?.returnStateFor(edit!)).toBe('phone');
});

it('returns edits opened from confirmation to confirmation', () => {
    const edit = state?.beginEdit('phone', 'confirmation');

    expect(state?.activeStateFor('confirmation', edit ?? null)).toBe('phone');
    expect(state?.returnStateFor(edit!)).toBe('confirmation');
});

it('does not use the active editor to reveal future exchanges', () => {
    expect(state?.completedConversationFor('phone')).toEqual({
        problem: true,
        phone: false,
        optionalDetails: false,
        workshop: false,
        confirmation: false,
    });
});
```

- [ ] **Step 2: Run the pure state tests and verify RED**

Run:

```bash
npm test -- resources/js/components/public-intake/state.test.ts
```

Expected: FAIL because the edit-context helpers do not exist.

- [ ] **Step 3: Implement the explicit edit context**

In `state.ts`, remove the `returnToConfirmation` parameter and the editing parameter from `historyRankFor`. Add:

```ts
export type EditContext = {
    answer: EditableAnswer;
    returnState: ConversationState;
};

export type CompletedConversation = {
    problem: boolean;
    phone: boolean;
    optionalDetails: boolean;
    workshop: boolean;
    confirmation: boolean;
};

export const beginEdit = (answer: EditableAnswer, returnState: ConversationState): EditContext => ({
    answer,
    returnState,
});

export const activeStateFor = (state: ConversationState, edit: EditContext | null): ConversationState => edit?.answer ?? state;

export const returnStateFor = (edit: EditContext): ConversationState => edit.returnState;

export const completedConversationFor = (state: ConversationState): CompletedConversation => {
    const rank = conversationRanks[state];

    return {
        problem: rank >= conversationRanks.phone,
        phone: rank >= conversationRanks['optional-details'],
        optionalDetails: rank >= conversationRanks.workshop,
        workshop: rank >= conversationRanks.confirmation,
        confirmation: state === 'confirmation' || state === 'success',
    };
};
```

Keep `advanceConversation()` responsible only for normal forward progression and validation:

```ts
export const advanceConversation = (
    state: ConversationState,
    payload: PublicIntakePayload,
    hasAvailableWorkshops: boolean,
): { state: ConversationState; error?: ConversationError } => {
    if (state === 'problem') {
        return payload.message.trim() === ''
            ? { state, error: { field: 'message', message: 'Tell us what is happening with your car.' } }
            : { state: 'phone' };
    }

    if (state === 'phone') {
        return payload.phone.trim() === ''
            ? { state, error: { field: 'phone', message: 'Add a phone number so the workshop can contact you.' } }
            : { state: 'optional-details' };
    }

    if (state === 'workshop') {
        return !hasAvailableWorkshops || payload.workshop_id === null
            ? { state, error: { field: 'workshop_id', message: 'Choose a workshop before continuing.' } }
            : { state: 'confirmation' };
    }

    return { state };
};
```

- [ ] **Step 4: Orchestrate edits without changing durable progress**

In `PublicIntakeFlow.vue`, replace `editingState` with:

```ts
const editContext = ref<EditContext | null>(null);
const activeState = computed(() => activeStateFor(conversationState.value, editContext.value));
const activeControl = computed(() => responseControlFor(activeState.value));
const completedConversation = computed(() => completedConversationFor(conversationState.value));
```

Split state activation from focus/scroll:

```ts
const focusActiveState = async (state: ConversationState, errors: IntakeErrors = {}) => {
    announcement.value = Object.keys(errors).length > 0
        ? 'Please check the highlighted answer.'
        : `Now showing ${state.replace('-', ' ')}.`;
    await nextTick();
    document.getElementById(focusTargets[state] ?? '')?.focus();
    scrollTranscriptToEnd();
};

const moveToState = async (state: ConversationState, errors: IntakeErrors = {}) => {
    conversationState.value = state;
    await focusActiveState(state, errors);
};

const editAnswer = async (answer: EditableAnswer) => {
    cancelPending.value = false;
    editContext.value = beginEdit(answer, conversationState.value);
    await focusActiveState(answer);
};
```

For problem and phone saves, validate the `activeState`; on success either close the edit and return to its saved state or perform normal progression:

```ts
const continueConversation = async () => {
    const answeredState = activeState.value;
    const result = advanceConversation(answeredState, form.data(), props.workshops.length > 0);

    if (result.error) {
        form.setError(result.error.field, result.error.message);
        await focusActiveState(answeredState, { [result.error.field]: result.error.message });
        return;
    }

    if (answeredState === 'problem') form.clearErrors('message');
    if (answeredState === 'phone') form.clearErrors('phone');

    if (editContext.value) {
        const returnState = returnStateFor(editContext.value);
        editContext.value = null;
        await moveToState(returnState);
        return;
    }

    await moveToState(result.state);
};
```

Add `data-testid="continue-problem"` to the problem Continue/Save button.

- [ ] **Step 5: Run state and rendered regression tests**

Run:

```bash
npm test -- resources/js/components/public-intake/state.test.ts resources/js/components/public-intake/PublicIntakeFlow.test.ts
```

Expected: PASS, including the exact description-edit-from-phone regression.

- [ ] **Step 6: Commit the state repair**

```bash
git add resources/js/components/public-intake/state.ts resources/js/components/public-intake/state.test.ts resources/js/components/public-intake/PublicIntakeFlow.vue resources/js/components/public-intake/PublicIntakeFlow.test.ts
git commit -m "fix(intake): preserve progress during edits"
```

---

### Task 3: Build the Bottom-Anchored Chat Viewport

**Files:**
- Create: `resources/js/components/public-intake/PublicIntakeTranscript.vue`
- Modify: `resources/js/components/public-intake/PublicIntakeFlow.vue`
- Modify: `resources/js/components/public-intake/PublicIntakeFlow.test.ts`
- Modify: `resources/js/pages/Welcome.test.ts`

**Interfaces:**
- `PublicIntakeTranscript` consumes completed flags, entered values, selected workshop, and current edit target.
- `PublicIntakeTranscript` emits `edit(answer: EditableAnswer)`.
- `PublicIntakeFlow` retains all form mutation, active composers, focus, and scrolling.

- [ ] **Step 1: Add failing chat-shell assertions**

Extend `PublicIntakeFlow.test.ts`:

```ts
it('keeps the composer docked below an internally scrolling transcript', () => {
    const wrapper = mount(PublicIntakeFlow, { props: { workshops: [] } });

    expect(wrapper.get('[data-testid="intake-chat"]').classes()).toContain('flex-col');
    expect(wrapper.get('[data-testid="intake-transcript"]').classes()).toContain('overflow-y-auto');
    expect(wrapper.get('[data-testid="intake-composer"]').classes()).toContain('shrink-0');
});

it('scrolls the transcript instead of the browser page after advancing', async () => {
    const wrapper = mount(PublicIntakeFlow, { props: { workshops: [] } });
    const transcript = wrapper.get('[data-testid="intake-transcript"]').element;

    await wrapper.get('#message').setValue('Brake noise');
    await wrapper.get('[data-testid="continue-problem"]').trigger('click');

    expect(transcript.scrollTo).toHaveBeenCalled();
    expect(Element.prototype.scrollIntoView).not.toHaveBeenCalled();
});
```

Update `Welcome.test.ts` so it keeps color/layout token checks but removes source-string assertions claiming that editability, cancellation, or submission behavior works.

- [ ] **Step 2: Run the focused tests and verify RED**

Run:

```bash
npm test -- resources/js/components/public-intake/PublicIntakeFlow.test.ts resources/js/pages/Welcome.test.ts
```

Expected: FAIL because the transcript/composer test hooks and internal scrolling do not exist.

- [ ] **Step 3: Extract the transcript presentation**

Create `PublicIntakeTranscript.vue` with this public contract:

```ts
<script setup lang="ts">
import type { CompletedConversation, EditableAnswer } from './state';

defineProps<{
    completed: CompletedConversation;
    message: string;
    phone: string;
    customerName: string;
    vehicleSummary: string;
    selectedWorkshopName?: string;
    editingAnswer?: EditableAnswer;
}>();

defineEmits<{
    edit: [answer: EditableAnswer];
}>();
</script>
```

Move only completed prompt/answer bubbles and their pencil buttons from `PublicIntakeFlow.vue` into this component. Gate each customer bubble by both its completed flag and a real value. Render the confirmation prompt only when `completed.confirmation` is true and `selectedWorkshopName` is present. Do not move inputs, validation, or form actions into the transcript.

- [ ] **Step 4: Implement the bounded chat shell**

Change the existing `<form>` opening tag to:

```vue
<form
    data-testid="intake-chat"
    class="public-card flex h-[calc(100dvh-7rem)] min-h-[32rem] max-h-[48rem] flex-col overflow-hidden"
    @submit.prevent="submit"
>
```

Wrap the transcript component and the existing active system-question branch in these exact containers:

```vue
<div
    ref="transcriptViewport"
    data-testid="intake-transcript"
    class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-4 py-5 sm:px-7 sm:py-7 lg:px-9"
>
    <div class="flex min-h-full flex-col justify-end space-y-5 sm:space-y-6">
        <PublicIntakeTranscript
            :completed="completedConversation"
            :message="form.message"
            :phone="form.phone"
            :customer-name="form.customer_name"
            :vehicle-summary="vehicleSummary"
            :selected-workshop-name="selectedWorkshop?.name"
            :editing-answer="editContext?.answer"
            @edit="editAnswer"
        />
        <div data-testid="active-question" class="space-y-5 sm:space-y-6">
            <!-- Existing active system-question elements are moved here verbatim. -->
        </div>
    </div>
</div>
```

Wrap the existing `activeControl` `v-if`/`v-else-if` chain in:

```vue
<div data-testid="intake-composer" class="shrink-0 border-t border-slate-200 bg-white px-4 py-4 sm:px-7 lg:px-9">
    <div class="mx-auto w-full">
        <!-- Existing activeControl elements are moved here verbatim. -->
    </div>
</div>
```

The two mechanical moves above preserve every current label, ID, error binding, radio semantic, cancellation action, and submit handler. Only completed prompt/answer bubbles move to `PublicIntakeTranscript`; only the current system question stays at the bottom of the transcript; only response inputs/actions live in the composer.

- [ ] **Step 5: Replace page scrolling with transcript scrolling**

Add:

```ts
const transcriptViewport = ref<HTMLElement | null>(null);

const scrollTranscriptToEnd = () => {
    const transcript = transcriptViewport.value;
    if (!transcript) return;

    const reduceMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;
    transcript.scrollTo({
        top: transcript.scrollHeight,
        behavior: reduceMotion ? 'auto' : 'smooth',
    });
};
```

Call it after `nextTick()` from progression, edit-open, edit-save, server-error routing, and reset. Remove `document.getElementById('active-response')?.scrollIntoView(...)`.

- [ ] **Step 6: Run focused chat tests**

Run:

```bash
npm test -- resources/js/components/public-intake/PublicIntakeFlow.test.ts resources/js/components/public-intake/state.test.ts resources/js/pages/Welcome.test.ts
```

Expected: PASS; the active composer stays docked and the exact edit regression remains fixed.

- [ ] **Step 7: Commit the chat viewport**

```bash
git add resources/js/components/public-intake/PublicIntakeTranscript.vue resources/js/components/public-intake/PublicIntakeFlow.vue resources/js/components/public-intake/PublicIntakeFlow.test.ts resources/js/pages/Welcome.test.ts
git commit -m "feat(intake): anchor conversational chat"
```

---

### Task 4: Allow Both Optional Details and Preserve Errors

**Files:**
- Modify: `resources/js/components/public-intake/state.ts`
- Modify: `resources/js/components/public-intake/state.test.ts`
- Modify: `resources/js/components/public-intake/PublicIntakeFlow.vue`
- Modify: `resources/js/components/public-intake/PublicIntakeFlow.test.ts`

**Interfaces:**
- `name` and `vehicle` editors both return to `optional-details` during initial entry.
- Optional editors opened from confirmation return to `confirmation`.
- Continue from `optional-details` is the only optional-to-workshop transition.

- [ ] **Step 1: Add failing optional-flow state tests**

Replace the old one-choice assertion with:

```ts
it('returns each initial optional editor to the optional menu', () => {
    expect(state?.beginEdit('name', 'optional-details')).toEqual({
        answer: 'name',
        returnState: 'optional-details',
    });
    expect(state?.beginEdit('vehicle', 'optional-details')).toEqual({
        answer: 'vehicle',
        returnState: 'optional-details',
    });
    expect(state?.finishOptionalDetails()).toBe('workshop');
});
```

- [ ] **Step 2: Add failing rendered optional and validation tests**

Add component tests that:

- enter phone, add and save name, return to optional menu;
- add and save vehicle afterward, return to optional menu;
- assert both summaries are visible before continuing;
- set `form.errors.customer_name` through the mocked failed POST callback, assert the exact message is visible, change the name, and assert only that error clears;
- set `vehicle.year` to `2200`, assert the editor does not advance and the year error remains associated with `#vehicle-year`.

Use explicit assertions such as:

```ts
expect(wrapper.text()).toContain('Name: Olena');
expect(wrapper.text()).toContain('Car: Opel · Insignia · 2018');
expect(wrapper.get('#customer-name').attributes('aria-describedby')).toBe('customer-name-error');
expect(wrapper.get('#vehicle-year').attributes('aria-invalid')).toBe('true');
```

- [ ] **Step 3: Run focused tests and verify RED**

Run:

```bash
npm test -- resources/js/components/public-intake/state.test.ts resources/js/components/public-intake/PublicIntakeFlow.test.ts
```

Expected: FAIL because the current flow advances after the first optional editor and clears unresolved errors.

- [ ] **Step 4: Implement the reusable optional menu**

Remove `optionalChoice` and use edit contexts:

```ts
const openOptionalEditor = async (answer: 'name' | 'vehicle') => {
    editContext.value = beginEdit(answer, conversationState.value);
    await focusActiveState(answer);
};

const continueFromOptionalDetails = async () => {
    await moveToState(finishOptionalDetails());
};
```

When the initial optional menu is active, `conversationState` remains `optional-details`; saving name or vehicle closes its edit context and returns there. Render Add/Edit name, Add/Edit car details, and Continue actions together. Continue works whether neither, one, or both optional values are present.

- [ ] **Step 5: Mirror backend constraints and show exact errors**

Add input constraints:

```vue
<textarea id="message" maxlength="5000" ... />
<input id="phone" maxlength="50" ... />
<input id="customer-name" maxlength="255" :aria-invalid="Boolean(form.errors.customer_name)" aria-describedby="customer-name-error" ... />
<input id="vehicle-brand" maxlength="255" :aria-invalid="Boolean(form.errors['vehicle.brand'])" aria-describedby="vehicle-brand-error" ... />
<input id="vehicle-model" maxlength="255" :aria-invalid="Boolean(form.errors['vehicle.model'])" aria-describedby="vehicle-model-error" ... />
<input id="vehicle-year" min="1886" max="2100" :aria-invalid="Boolean(form.errors['vehicle.year'])" aria-describedby="vehicle-year-error" ... />
<input id="vehicle-license-plate" maxlength="255" :aria-invalid="Boolean(form.errors['vehicle.license_plate'])" aria-describedby="vehicle-license-plate-error" ... />
```

Render one error element per field using its exact `form.errors` value. Delete the blanket error clearing from `saveOptionalDetails()`. Clear only the edited field from its input handler.

Before closing the vehicle editor, reject a non-null year outside `1886..2100` with the same customer-facing range message used by the server. If any active optional field still has an error, focus the first invalid field and do not close the editor.

- [ ] **Step 6: Route server errors into an edit context**

Because final submission starts from confirmation, replace direct state mutation in `onError` with:

```ts
onError: async (errors) => {
    const targetState = stateForServerErrors(errors);
    if (!targetState) return;

    editContext.value = beginEdit(targetState as EditableAnswer, conversationState.value);
    await focusActiveState(targetState, errors);
},
```

Keep `conversationState` at confirmation while the invalid answer is edited; after a valid save, return to confirmation.

- [ ] **Step 7: Run optional-flow tests**

Run:

```bash
npm test -- resources/js/components/public-intake/state.test.ts resources/js/components/public-intake/PublicIntakeFlow.test.ts
```

Expected: PASS for both optional orders, field-specific errors, and confirmation return behavior.

- [ ] **Step 8: Commit the optional-flow repair**

```bash
git add resources/js/components/public-intake/state.ts resources/js/components/public-intake/state.test.ts resources/js/components/public-intake/PublicIntakeFlow.vue resources/js/components/public-intake/PublicIntakeFlow.test.ts
git commit -m "fix(intake): support complete optional details"
```

---

### Task 5: Use Browser Navigation for Admin Hosts

**Files:**
- Modify: `resources/js/components/public/PublicHeader.vue`
- Create: `resources/js/components/public/PublicHeader.test.ts`
- Modify: `resources/js/pages/PublicExperienceDesign.test.ts`
- Modify: `resources/js/pages/Welcome.test.ts`

**Interfaces:**
- Same-origin home/customer destinations remain Inertia `Link` components.
- `adminLoginUrl` and `adminRegisterUrl` render as native anchors on desktop and mobile.

- [ ] **Step 1: Add a failing rendered header test**

Create `PublicHeader.test.ts` using jsdom and a `Link` stub that renders `data-inertia-link="true"`. Mount with split-host URLs and assert:

```ts
expect(wrapper.findAll('a[data-inertia-link="true"]')).toHaveLength(3);
expect(wrapper.findAll('a[href="https://admin.autoservice.test/login"]')).toHaveLength(2);
expect(wrapper.findAll('a[href="https://admin.autoservice.test/register"]')).toHaveLength(2);
expect(wrapper.find('a[href="https://admin.autoservice.test/login"]').attributes('data-inertia-link')).toBeUndefined();
```

The two native anchors account for desktop and mobile markup.

- [ ] **Step 2: Run the header test and verify RED**

Run:

```bash
npm test -- resources/js/components/public/PublicHeader.test.ts
```

Expected: FAIL because all four admin destinations currently use Inertia `Link`.

- [ ] **Step 3: Replace only admin `Link` components**

Change the desktop and mobile staff destinations from:

```vue
<Link :href="adminLoginUrl">...</Link>
<Link :href="adminRegisterUrl">...</Link>
```

to:

```vue
<a :href="adminLoginUrl">...</a>
<a :href="adminRegisterUrl">...</a>
```

Preserve conditions, copy, icons, classes, and accessible names. Keep home and My requests as Inertia `Link` components.

- [ ] **Step 4: Remove misleading source-string behavior assertions**

Keep visual tokens and required copy in `PublicExperienceDesign.test.ts` and `Welcome.test.ts`. Remove assertions that merely check the strings `adminLoginUrl`, `editAnswer`, `form.post`, or event-handler names as proof of behavior; rendered tests now own those guarantees.

- [ ] **Step 5: Run the focused header tests**

Run:

```bash
npm test -- resources/js/components/public/PublicHeader.test.ts resources/js/pages/PublicExperienceDesign.test.ts resources/js/pages/Welcome.test.ts
```

Expected: PASS with native admin anchors and same-origin Inertia links.

- [ ] **Step 6: Commit the navigation repair**

```bash
git add resources/js/components/public/PublicHeader.vue resources/js/components/public/PublicHeader.test.ts resources/js/pages/PublicExperienceDesign.test.ts resources/js/pages/Welcome.test.ts
git commit -m "fix(public): use native admin links"
```

---

### Task 6: Focused Regression and Visual Verification

**Files:**
- Modify only files listed above if verification exposes a defect.

**Interfaces:**
- Consumes the completed chat, state, validation, and header repairs.
- Produces a verified homepage flow without changing backend behavior.

- [ ] **Step 1: Run the complete focused frontend set**

Run:

```bash
npm test -- resources/js/components/public-intake/PublicIntakeFlow.test.ts resources/js/components/public-intake/state.test.ts resources/js/components/public/PublicHeader.test.ts resources/js/pages/PublicExperienceDesign.test.ts resources/js/pages/Welcome.test.ts resources/js/pages/CustomerPortal.test.ts resources/js/pages/AuthPublicDesign.test.ts
```

Expected: all focused frontend tests PASS with zero failures.

- [ ] **Step 2: Run focused Laravel regressions**

Run:

```bash
php artisan test tests/Feature/PublicIntakeSubmissionTest.php tests/Feature/PublicAdminDomainRoutingTest.php
```

Expected: all public intake and split-host feature tests PASS. If the sandbox blocks PostgreSQL, rerun the same command with approved external access; do not switch databases.

- [ ] **Step 3: Check formatting without rewriting unrelated files**

Run:

```bash
npx prettier --check resources/js/components/public-intake/PublicIntakeFlow.vue resources/js/components/public-intake/PublicIntakeTranscript.vue resources/js/components/public-intake/PublicIntakeFlow.test.ts resources/js/components/public-intake/state.ts resources/js/components/public-intake/state.test.ts resources/js/components/public/PublicHeader.vue resources/js/components/public/PublicHeader.test.ts resources/js/pages/Welcome.test.ts resources/js/pages/PublicExperienceDesign.test.ts
```

Expected: all listed files use Prettier formatting. If formatting fails, format only the listed files and rerun the focused tests once.

- [ ] **Step 4: Perform the screenshot regression manually**

Using the already-running local site:

1. Open `/` at desktop width.
2. Enter a description and continue to phone.
3. Edit and save the description.
4. Confirm phone remains the active composer.
5. Confirm optional, workshop, and confirmation exchanges are absent.
6. Complete phone, add both name and vehicle, then continue.
7. Confirm the newest exchange stays at the bottom and older messages scroll inside the card.
8. Edit description from confirmation and confirm the flow returns to confirmation.
9. Confirm mobile width keeps the composer reachable and workshop cards stacked.

Expected: the browser page does not grow into the Screenshot 3 state; transcript scrolling is internal and no blank bubbles appear.

- [ ] **Step 5: Inspect the final diff**

Run:

```bash
git diff --check
git status --short
git diff -- resources/js/components/public-intake resources/js/components/public/PublicHeader.vue resources/js/components/public/PublicHeader.test.ts resources/js/pages/Welcome.test.ts resources/js/pages/PublicExperienceDesign.test.ts package.json package-lock.json
```

Expected: no whitespace errors, no unrelated changes introduced by this repair, and no second form or POST call.

- [ ] **Step 6: Commit verification-only corrections if needed**

If verification required a correction, stage only the affected files and commit:

```bash
git commit -m "fix(intake): resolve chat regressions"
```

If no corrections were required, do not create an empty commit.
