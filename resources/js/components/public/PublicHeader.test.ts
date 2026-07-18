// @vitest-environment jsdom

import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import PublicHeader from './PublicHeader.vue';

vi.mock('@inertiajs/vue3', () => ({
    Link: defineComponent({
        props: { href: { type: String, required: true } },
        setup(props, { slots }) {
            return () => h('a', { href: props.href, 'data-inertia-link': 'true' }, slots.default?.());
        },
    }),
}));

describe('PublicHeader', () => {
    it('uses native anchors for cross-origin admin destinations', () => {
        const wrapper = mount(PublicHeader, {
            props: {
                canLogin: true,
                canRegister: true,
                adminLoginUrl: 'https://admin.autoservice.test/login',
                adminRegisterUrl: 'https://admin.autoservice.test/register',
            },
            global: {
                mocks: { route: vi.fn((name: string) => `/${name}`) },
            },
        });

        expect(wrapper.findAll('a[data-inertia-link="true"]')).toHaveLength(3);
        expect(wrapper.findAll('a[href="https://admin.autoservice.test/login"]')).toHaveLength(2);
        expect(wrapper.findAll('a[href="https://admin.autoservice.test/register"]')).toHaveLength(2);
        expect(wrapper.find('a[href="https://admin.autoservice.test/login"]').attributes('data-inertia-link')).toBeUndefined();
        expect(wrapper.find('a[href="https://admin.autoservice.test/register"]').attributes('data-inertia-link')).toBeUndefined();
    });
});
