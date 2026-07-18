import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

const source = (path: string): string => readFileSync(resolve(__dirname, path), 'utf8');

describe('dashboard booking-request workflow', () => {
    it('shows the linked repair-order status', () => {
        const bookingRequestShow = source('BookingRequests/Show.vue');

        expect(bookingRequestShow).toContain('linkedRepairOrder.status.label');
    });

    it('initializes new vehicle fields from server defaults', () => {
        const repairOrderCreate = source('RepairOrders/Create.vue');

        expect(repairOrderCreate).toContain('new_vehicle: { ...props.defaults.new_vehicle }');
    });
});
