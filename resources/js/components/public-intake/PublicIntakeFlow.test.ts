// @vitest-environment jsdom

import { mount } from '@vue/test-utils';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive } from 'vue';
import PublicIntakeFlow from './PublicIntakeFlow.vue';

const appCssSource = readFileSync(resolve(__dirname, '../../../css/app.css'), 'utf8');

const inertia = vi.hoisted(() => ({
    post: vi.fn(),
    form: null as Record<string, any> | null,
}));

vi.mock('@inertiajs/vue3', () => ({
    useForm: <T extends Record<string, unknown>>(defaults: T) => {
        const clone = <Value>(value: Value): Value => JSON.parse(JSON.stringify(value)) as Value;
        const initial = clone(defaults);
        const fieldNames = Object.keys(initial) as Array<keyof T>;
        const form = reactive({
            ...clone(defaults),
            errors: {} as Record<string, string>,
            processing: false,
            data: () => Object.fromEntries(fieldNames.map((field) => [field, clone(form[field])])) as T,
            setError: (field: string, message: string) => {
                form.errors[field] = message;
            },
            clearErrors: (...fields: string[]) => {
                if (fields.length === 0) {
                    form.errors = {};
                    return;
                }

                fields.forEach((field) => delete form.errors[field]);
            },
            reset: () => {
                fieldNames.forEach((field) => {
                    form[field] = clone(initial[field]);
                });
            },
            post: inertia.post,
        });
        inertia.form = form;

        return form;
    },
}));

const buttonWithText = (wrapper: ReturnType<typeof mount>, label: string) =>
    wrapper.findAll('button').find((button) => button.text().includes(label));

const advanceToOptionalDetails = async (wrapper: ReturnType<typeof mount>) => {
    await wrapper.get('#message').setValue('Brake noise');
    await buttonWithText(wrapper, 'Send')?.trigger('click');
    await wrapper.get('#phone').setValue('+380501112233');
    await buttonWithText(wrapper, 'Continue')?.trigger('click');
};

const advanceToConfirmation = async (wrapper: ReturnType<typeof mount>) => {
    await advanceToOptionalDetails(wrapper);
    await buttonWithText(wrapper, 'Continue')?.trigger('click');
    const workshop = wrapper.get('#workshop-option-1');
    (workshop.element as HTMLInputElement).checked = true;
    await workshop.trigger('change');
};

describe('PublicIntakeFlow', () => {
    beforeEach(() => {
        inertia.post.mockReset();
        vi.stubGlobal(
            'route',
            vi.fn((name: string) => `/${name}`),
        );
        HTMLElement.prototype.scrollTo = vi.fn();
        Element.prototype.scrollIntoView = vi.fn();
    });

    it('starts with only the compact problem composer', () => {
        const wrapper = mount(PublicIntakeFlow, {
            props: { workshops: [{ id: 1, name: 'Main Auto' }] },
        });

        expect(wrapper.find('[data-testid="intake-starter"]').exists()).toBe(true);
        expect(wrapper.find('[data-testid="intake-chat"]').exists()).toBe(false);
        expect(wrapper.find('[data-testid="intake-transcript"]').exists()).toBe(false);
        expect(wrapper.find('#message').exists()).toBe(true);
        expect(buttonWithText(wrapper, 'Send')).toBeDefined();
        expect(wrapper.get('[data-testid="problem-composer"]').classes()).toContain('flex');
        expect(wrapper.get('[data-testid="intake-starter"]').text()).not.toContain('The workshop will confirm the diagnosis.');
    });

    it('shows useful starter content and fills without sending a prompt example', async () => {
        const wrapper = mount(PublicIntakeFlow, {
            props: { workshops: [{ id: 1, name: 'Main Auto' }] },
            attachTo: document.body,
        });

        expect(wrapper.get('[data-testid="intake-intro"]').text()).toContain('What is happening with your car?');
        expect(wrapper.text()).toContain('The car will not start');

        await buttonWithText(wrapper, 'The car will not start')?.trigger('click');

        expect((wrapper.get('#message').element as HTMLTextAreaElement).value).toBe('The car will not start');
        expect(document.activeElement).toBe(wrapper.get('#message').element);
        expect(wrapper.find('[data-testid="intake-chat"]').exists()).toBe(false);
        expect(inertia.post).not.toHaveBeenCalled();

        wrapper.unmount();
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

    it('returns to phone after editing the description from the phone question', async () => {
        const wrapper = mount(PublicIntakeFlow, {
            props: { workshops: [{ id: 1, name: 'Main Auto' }] },
        });

        await wrapper.get('#message').setValue('Brake noise');
        await buttonWithText(wrapper, 'Send')?.trigger('click');
        await wrapper.get('[aria-label="Edit problem description"]').trigger('click');
        await wrapper.get('#message').setValue('Brake noise when stopping');
        await buttonWithText(wrapper, 'Save changes')?.trigger('click');

        expect(wrapper.find('[data-testid="intake-chat"]').exists()).toBe(true);
        expect(wrapper.find('#phone').exists()).toBe(true);
        expect(wrapper.text()).not.toContain('Would you like to add anything else?');
        expect(wrapper.text()).not.toContain('Where should we send your request?');
        expect(wrapper.text()).not.toContain('Ready to send your request');
    });

    it('keeps the composer docked below an internally scrolling transcript', async () => {
        const wrapper = mount(PublicIntakeFlow, { props: { workshops: [] } });
        await wrapper.get('#message').setValue('Brake noise');
        await buttonWithText(wrapper, 'Send')?.trigger('click');

        expect(wrapper.get('[data-testid="intake-chat"]').classes()).toContain('flex-col');
        expect(wrapper.get('[data-testid="intake-transcript"]').classes()).toContain('overflow-y-auto');
        expect(wrapper.get('[data-testid="intake-composer"]').classes()).toContain('shrink-0');
    });

    it('keeps one bounded workspace shell before and after expansion', async () => {
        const wrapper = mount(PublicIntakeFlow, { props: { workshops: [{ id: 1, name: 'Main Auto' }] } });
        const shell = wrapper.get('[data-testid="intake-workspace"]');

        await wrapper.get('#message').setValue('Brake noise');
        await buttonWithText(wrapper, 'Send')?.trigger('click');

        expect(wrapper.get('[data-testid="intake-workspace"]').element).toBe(shell.element);
        expect(wrapper.get('[data-testid="intake-transcript"]').classes()).toContain('overflow-y-auto');
        expect(wrapper.get('[data-testid="intake-composer"]').classes()).toContain('shrink-0');
    });

    it('defines motion-safe intake reveal styles', () => {
        expect(appCssSource).toContain('.intake-reveal-enter-active');
        expect(appCssSource).toContain('@media (prefers-reduced-motion: reduce)');
    });

    it('scrolls the transcript instead of the browser page after advancing', async () => {
        const wrapper = mount(PublicIntakeFlow, { props: { workshops: [] } });

        await wrapper.get('#message').setValue('Brake noise');
        await buttonWithText(wrapper, 'Send')?.trigger('click');
        const transcript = wrapper.get('[data-testid="intake-transcript"]').element as HTMLElement;

        expect(transcript.scrollTo).toHaveBeenCalled();
        expect(Element.prototype.scrollIntoView).not.toHaveBeenCalled();
    });

    it('allows name and vehicle details to be added before workshop selection', async () => {
        const wrapper = mount(PublicIntakeFlow, {
            props: { workshops: [{ id: 1, name: 'Main Auto' }] },
        });
        await advanceToOptionalDetails(wrapper);

        await buttonWithText(wrapper, 'Add name')?.trigger('click');
        await wrapper.get('#customer-name').setValue('Olena');
        await buttonWithText(wrapper, 'Save name')?.trigger('click');

        expect(buttonWithText(wrapper, 'Add car details')).toBeDefined();
        await buttonWithText(wrapper, 'Add car details')?.trigger('click');
        await wrapper.get('#vehicle-brand').setValue('Opel');
        await wrapper.get('#vehicle-model').setValue('Insignia');
        await wrapper.get('#vehicle-year').setValue('2018');
        await buttonWithText(wrapper, 'Save car details')?.trigger('click');

        expect(wrapper.text()).toContain('Name: Olena');
        expect(wrapper.text()).toContain('Car: Opel · Insignia · 2018');
        expect(buttonWithText(wrapper, 'Continue')).toBeDefined();
        expect(wrapper.text()).not.toContain('Where should we send your request?');
    });

    it('shows and clears the exact customer name error returned by the server', async () => {
        const wrapper = mount(PublicIntakeFlow, {
            props: { workshops: [{ id: 1, name: 'Main Auto' }] },
        });
        await advanceToConfirmation(wrapper);
        const sendRequest = buttonWithText(wrapper, 'Send request');
        expect(sendRequest).toBeDefined();
        await wrapper.get('form').trigger('submit');

        const errors = { customer_name: 'The customer name must not be greater than 255 characters.' };
        Object.assign(inertia.form!.errors, errors);
        const options = inertia.post.mock.calls[0][1] as { onError: (errors: Record<string, string>) => Promise<void> };
        await options.onError(errors);

        expect(wrapper.get('#customer-name').attributes('aria-describedby')).toBe('customer-name-error');
        expect(wrapper.get('#customer-name').attributes('aria-invalid')).toBe('true');
        expect(wrapper.get('#customer-name-error').text()).toBe(errors.customer_name);

        await wrapper.get('#customer-name').setValue('Olena');
        expect(wrapper.find('#customer-name-error').exists()).toBe(false);
    });

    it('keeps an out-of-range vehicle year in the editor', async () => {
        const wrapper = mount(PublicIntakeFlow, {
            props: { workshops: [{ id: 1, name: 'Main Auto' }] },
        });
        await advanceToOptionalDetails(wrapper);
        await buttonWithText(wrapper, 'Add car details')?.trigger('click');
        await wrapper.get('#vehicle-year').setValue('2200');
        await buttonWithText(wrapper, 'Save car details')?.trigger('click');

        expect(wrapper.find('#vehicle-year').exists()).toBe(true);
        expect(wrapper.get('#vehicle-year').attributes('aria-invalid')).toBe('true');
        expect(wrapper.get('#vehicle-year-error').text()).toContain('1886');
        expect(wrapper.get('#vehicle-year-error').text()).toContain('2100');
    });

    it('posts only from confirmation with the selected workshop', async () => {
        const wrapper = mount(PublicIntakeFlow, {
            props: { workshops: [{ id: 1, name: 'Main Auto' }] },
        });

        await wrapper.get('form').trigger('submit');
        expect(inertia.post).not.toHaveBeenCalled();

        await advanceToConfirmation(wrapper);
        await wrapper.get('form').trigger('submit');

        expect(inertia.post).toHaveBeenCalledTimes(1);
        expect(inertia.post).toHaveBeenCalledWith('/public-intake.store', expect.objectContaining({ preserveScroll: true }));
        expect(inertia.form?.workshop_id).toBe(1);
    });

    it('confirms cancellation and clears only the local draft', async () => {
        const wrapper = mount(PublicIntakeFlow, {
            props: { workshops: [{ id: 1, name: 'Main Auto' }] },
        });
        await advanceToConfirmation(wrapper);

        await buttonWithText(wrapper, 'Cancel request')?.trigger('click');
        expect(wrapper.get('[role="alertdialog"]').text()).toContain('Cancel this draft?');

        await buttonWithText(wrapper, 'Yes, cancel draft')?.trigger('click');

        expect(wrapper.find('[role="alertdialog"]').exists()).toBe(false);
        expect(wrapper.find('#message').exists()).toBe(true);
        expect((wrapper.get('#message').element as HTMLTextAreaElement).value).toBe('');
        expect(inertia.post).not.toHaveBeenCalled();
        expect(wrapper.emitted('expanded-change')).toEqual([[true], [false]]);
        expect(wrapper.find('[data-testid="intake-starter"]').exists()).toBe(true);
        expect(wrapper.find('[data-testid="intake-chat"]').exists()).toBe(false);
    });

    it('uses accessible workshop radios and explains empty availability', async () => {
        const withWorkshop = mount(PublicIntakeFlow, {
            props: { workshops: [{ id: 1, name: 'Main Auto' }] },
        });
        await advanceToOptionalDetails(withWorkshop);
        await buttonWithText(withWorkshop, 'Continue')?.trigger('click');

        expect(withWorkshop.get('#workshop-option-1').attributes('type')).toBe('radio');
        expect(withWorkshop.get('#workshop-option-1').element.closest('label')?.textContent).toContain('Main Auto');

        const withoutWorkshop = mount(PublicIntakeFlow, { props: { workshops: [] } });
        await advanceToOptionalDetails(withoutWorkshop);
        await buttonWithText(withoutWorkshop, 'Continue')?.trigger('click');

        expect(withoutWorkshop.text()).toContain('No workshops are available to receive requests right now.');
    });
});
