// @vitest-environment jsdom

import { mount } from '@vue/test-utils';
import { existsSync, readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';
import CustomerRequestDetail from './CustomerRequestDetail.vue';
import type { CustomerRequestDetail as CustomerRequestDetailPayload } from './types';

const request = (overrides: Partial<CustomerRequestDetailPayload> = {}): CustomerRequestDetailPayload => ({
    id: 42,
    title: 'Brake noise',
    problemDescription: 'A loud grinding noise appears when braking downhill.',
    status: { value: 'confirmed', label: 'Confirmed' },
    workshopName: 'Main Auto',
    submittedAt: '2026-07-17T08:00:00+03:00',
    updatedAt: '2026-07-18T09:30:00+03:00',
    customerName: 'Olena',
    vehicle: { brand: 'Toyota', model: 'Corolla', year: 2018, licensePlate: 'AA 1234 BB' },
    repairOrder: null,
    ...overrides,
});

describe('CustomerRequestDetail', () => {
    it('renders customer-safe request details and truthful milestones without messaging controls', () => {
        const wrapper = mount(CustomerRequestDetail, { props: { request: request() } });

        expect(wrapper.text()).toContain('A loud grinding noise appears when braking downhill.');
        expect(wrapper.text()).toContain('Confirmed');
        expect(wrapper.text()).toContain('Main Auto');
        expect(wrapper.text()).toContain('Olena');
        expect(wrapper.text()).toContain('Toyota');
        expect(wrapper.text()).toContain('Corolla');
        expect(wrapper.text()).toContain('2018');
        expect(wrapper.text()).toContain('AA 1234 BB');
        expect(wrapper.get('time[datetime="2026-07-17T08:00:00+03:00"]').exists()).toBe(true);
        expect(wrapper.get('time[datetime="2026-07-18T09:30:00+03:00"]').exists()).toBe(true);
        expect(wrapper.get('ol[aria-label="Request progress"]').text()).toContain('Request submitted');
        expect(wrapper.get('ol[aria-label="Request progress"]').text()).toContain('Current status');
        expect(wrapper.find('textarea').exists()).toBe(false);
        expect(wrapper.text()).not.toContain('Send message');
    });

    it('omits empty optional customer and vehicle rows', () => {
        const wrapper = mount(CustomerRequestDetail, {
            props: { request: request({ customerName: null, vehicle: {}, problemDescription: null }) },
        });

        expect(wrapper.find('[data-testid="customer-name"]').exists()).toBe(false);
        expect(wrapper.find('[data-testid="vehicle-details"]').exists()).toBe(false);
        expect(wrapper.find('[data-testid="problem-description"]').exists()).toBe(false);
    });

    it('renders a linked repair order and its progress milestone', () => {
        const wrapper = mount(CustomerRequestDetail, {
            props: {
                request: request({
                    repairOrder: {
                        id: 73,
                        status: { value: 'in_progress', label: 'In progress' },
                        openedAt: '2026-07-18T08:30:00+03:00',
                        updatedAt: '2026-07-18T10:00:00+03:00',
                    },
                }),
            },
        });

        expect(wrapper.get('[data-testid="repair-order-summary"]').text()).toContain('Repair order #73');
        expect(wrapper.get('[data-testid="repair-order-summary"]').text()).toContain('In progress');
        expect(wrapper.get('ol[aria-label="Request progress"]').text()).toContain('Repair order created');
        expect(wrapper.get('ol[aria-label="Request progress"]').text()).toContain('Current order status: In progress');
    });

    it('omits repair order details when no order is linked', () => {
        const wrapper = mount(CustomerRequestDetail, {
            props: { request: request({ repairOrder: null }) },
        });

        expect(wrapper.find('[data-testid="repair-order-summary"]').exists()).toBe(false);
        expect(wrapper.get('ol[aria-label="Request progress"]').text()).not.toContain('Repair order created');
    });

    it('composes the detail page in the public workspace with selected history', () => {
        const showPath = resolve(__dirname, '../../pages/CustomerPortal/Show.vue');
        const showSource = existsSync(showPath) ? readFileSync(showPath, 'utf8') : '';

        expect(showSource).toContain('PublicWorkspaceLayout');
        expect(showSource).toContain('CustomerRequestHistory');
        expect(showSource).toContain(':selected-request-id="request.id"');
        expect(showSource).toContain('CustomerRequestDetail');
        expect(showSource).not.toContain('PublicIntakeFlow');
    });
});
