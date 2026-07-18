// @vitest-environment jsdom

import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import CustomerRequestHistory from './CustomerRequestHistory.vue';
import { requestGroup } from './requestHistory';
import type { CustomerRequestSummary } from './types';

vi.mock('@inertiajs/vue3', () => ({
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
}));

const route = (name: string, id?: number) => (name === 'customer-portal.show' ? `/my-requests/${id}` : '/my-requests');

const request = (overrides: Partial<CustomerRequestSummary> = {}): CustomerRequestSummary => ({
    id: 1,
    title: 'Brake noise when slowing down',
    status: { value: 'new', label: 'New' },
    workshopName: 'Main Auto',
    submittedAt: '2026-07-18T08:00:00+03:00',
    updatedAt: '2026-07-18T09:00:00+03:00',
    ...overrides,
});

const mountHistory = (props: { requests?: CustomerRequestSummary[]; hasMore?: boolean; selectedRequestId?: number } = {}) =>
    mount(CustomerRequestHistory, {
        props: {
            requests: props.requests ?? [request()],
            hasMore: props.hasMore ?? false,
            selectedRequestId: props.selectedRequestId,
        },
        global: { mocks: { route } },
    });

describe('requestGroup', () => {
    const now = new Date('2026-07-18T12:00:00+03:00');

    it('groups calendar dates into Today, Last 7 days excluding Today, and Earlier', () => {
        expect(requestGroup('2026-07-18T00:01:00+03:00', now)).toBe('Today');
        expect(requestGroup('2026-07-12T23:59:00+03:00', now)).toBe('Last 7 days');
        expect(requestGroup('2026-07-11T23:59:00+03:00', now)).toBe('Earlier');
    });
});

describe('CustomerRequestHistory', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2026-07-18T12:00:00+03:00'));
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('renders grouped request links with compact customer-safe metadata', () => {
        const wrapper = mountHistory({
            requests: [
                request(),
                request({ id: 2, title: 'Scheduled maintenance', submittedAt: '2026-07-15T10:00:00+03:00' }),
                request({ id: 3, title: 'Will not start', submittedAt: '2026-06-30T10:00:00+03:00' }),
            ],
        });

        expect(wrapper.text()).toContain('Today');
        expect(wrapper.text()).toContain('Last 7 days');
        expect(wrapper.text()).toContain('Earlier');
        expect(wrapper.text()).toContain('New');
        expect(wrapper.text()).toContain('Main Auto');
        expect(wrapper.get('[data-testid="request-title-1"]').classes()).toContain('truncate');
        expect(wrapper.get('a[href="/my-requests/2"]').text()).toContain('Scheduled maintenance');
    });

    it('marks the selected request as the current page', () => {
        expect(mountHistory({ selectedRequestId: 1 }).get('a[href="/my-requests/1"]').attributes('aria-current')).toBe('page');
    });

    it('shows the complete-list action only when more requests exist', () => {
        expect(mountHistory({ hasMore: true }).get('a[href="/my-requests"]').text()).toContain('Show all requests');
        expect(mountHistory({ hasMore: false }).text()).not.toContain('Show all requests');
    });
});
