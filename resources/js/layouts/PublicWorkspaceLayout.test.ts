// @vitest-environment jsdom

import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import PublicWorkspaceLayout from './PublicWorkspaceLayout.vue';

vi.mock('@inertiajs/vue3', () => ({
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
}));

const route = (name: string) => `/${name}`;

const mountLayout = () =>
    mount(PublicWorkspaceLayout, {
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
        global: { mocks: { route } },
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

    it('describes the mobile navigation dialog without accessibility warnings', async () => {
        const warn = vi.spyOn(console, 'warn').mockImplementation(() => undefined);
        const wrapper = mountLayout();

        await wrapper.get('[aria-label="Open navigation"]').trigger('click');
        await wrapper.vm.$nextTick();

        expect(warn.mock.calls.flat().join(' ')).not.toContain('Missing `Description`');

        wrapper.unmount();
        warn.mockRestore();
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
            global: { mocks: { route } },
        });
        await wrapper.get('button[aria-label="Open Prices"]').trigger('click');
        await wrapper.get('button[aria-label="Close information panel"]').trigger('click');
        expect((wrapper.get('[data-testid="draft"]').element as HTMLInputElement).value).toBe('Brake noise');
    });
});
