// @vitest-environment jsdom

import type { CustomerRequestSummary, Paginated } from '@/components/public-portal/types';
import { mount } from '@vue/test-utils';
import { existsSync, readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it, vi } from 'vitest';
import Index from './CustomerPortal/Index.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div><slot /></div>' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
}));

const source = (path: string): string => {
    const absolutePath = resolve(__dirname, path);

    return existsSync(absolutePath) ? readFileSync(absolutePath, 'utf8') : '';
};

const layout = source('../layouts/CustomerPortalLayout.vue');
const requestAccess = source('CustomerPortal/RequestAccess.vue');
const verifyCode = source('CustomerPortal/VerifyCode.vue');
const index = source('CustomerPortal/Index.vue');

const route = (name: string, id?: number) => (name === 'customer-portal.show' ? `/my-requests/${id}` : name === 'home' ? '/' : '/my-requests');

const request: CustomerRequestSummary = {
    id: 42,
    title: 'Brake noise',
    status: { value: 'new', label: 'New' },
    workshopName: 'Main Auto',
    submittedAt: '2026-07-18T08:00:00+03:00',
    updatedAt: '2026-07-18T09:00:00+03:00',
};

const pagination = (data: CustomerRequestSummary[]): Paginated<CustomerRequestSummary> => ({
    data,
    current_page: 1,
    last_page: 1,
    links: [],
});

const mountIndex = (data: CustomerRequestSummary[]) =>
    mount(Index, {
        props: {
            recentRequests: data,
            hasMoreRequests: false,
            requests: pagination(data),
            canLogin: true,
            canRegister: true,
            adminLoginUrl: 'https://admin.test/login',
            adminRegisterUrl: 'https://admin.test/register',
        },
        global: {
            mocks: { route },
            stubs: {
                PublicWorkspaceLayout: {
                    template: '<div data-testid="public-workspace"><aside><slot name="history" /></aside><main><slot /></main></div>',
                },
            },
        },
    });

describe('Customer Portal public pages', () => {
    it('renders access and verification through the shared public workspace shell', () => {
        expect(layout).toContain('PublicWorkspaceLayout');
        expect(layout).toContain('public-card');
        expect(layout).toContain('max-w-md');
        expect(layout).toContain('rounded-[1.75rem]');
        expect(requestAccess).toContain('CustomerPortalLayout');
        expect(verifyCode).toContain('CustomerPortalLayout');
    });

    it('requests only a phone number without account or record-existence language', () => {
        expect(requestAccess).toContain('Access your service requests');
        expect(requestAccess).toContain('Phone number');
        expect(requestAccess).toContain('autocomplete="tel"');
        expect(requestAccess).toContain('aria-describedby="phone-help phone-error"');
        expect(requestAccess).toContain(':aria-invalid="form.errors.phone ? true : undefined"');
        expect(requestAccess).toContain("route('customer-portal.access.store')");
        expect(requestAccess).toContain('sessionExpired');
        expect(requestAccess).toContain('aria-live="polite"');
        expect(requestAccess).not.toMatch(/customer account|create account|password|no requests|not found/i);
    });

    it('provides an accessible one-time-code step with neutral messaging', () => {
        expect(verifyCode).toContain('Check your phone');
        expect(verifyCode).toContain('maskedPhone');
        expect(verifyCode).toContain('Verification code');
        expect(verifyCode).toContain('autocomplete="one-time-code"');
        expect(verifyCode).toContain('inputmode="numeric"');
        expect(verifyCode).toContain('aria-describedby="code-help code-error"');
        expect(verifyCode).toContain(':aria-invalid="form.errors.code ? true : undefined"');
        expect(verifyCode).toContain('aria-live="polite"');
        expect(verifyCode).toContain("route('customer-portal.verify.store')");
        expect(verifyCode).toContain("route('customer-portal.access.create')");
        expect(verifyCode).not.toMatch(/customer account|password|phone.*not found|no records/i);
    });

    it('renders full request rows and verified sidebar history', () => {
        const wrapper = mountIndex([request]);

        expect(wrapper.get('[data-testid="public-workspace"]').exists()).toBe(true);
        expect(wrapper.get('[data-testid="customer-request-history"]').text()).toContain('Brake noise');
        expect(wrapper.get('[data-testid="request-list"]').text()).toContain('New');
        expect(wrapper.get('[data-testid="request-list"]').text()).toContain('Main Auto');
        expect(wrapper.get('a[href="/my-requests/42"]').text()).toContain('Brake noise');
        expect(wrapper.find('time[datetime="2026-07-18T08:00:00+03:00"]').exists()).toBe(true);
    });

    it('guides an empty verified customer to create a new request', () => {
        const wrapper = mountIndex([]);

        expect(wrapper.text()).toContain('You do not have any service requests yet.');
        expect(wrapper.get('a[href="/"]').text()).toContain('Create a new request');
    });

    it('keeps deferred portal features out of the request list', () => {
        expect(index).not.toMatch(/repairOrders|estimates|documents|Send message/i);
    });
});
