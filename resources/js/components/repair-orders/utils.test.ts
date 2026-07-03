import { describe, expect, it } from 'vitest';
import type { RepairOrderVehicle } from './types';
import {
    canCancelRepairOrder,
    canCompleteRepairOrder,
    centsToDecimalInput,
    decimalInputToCents,
    formatCents,
    formatDate,
    formatDateTime,
    vehicleSummary,
} from './utils';

describe('canCompleteRepairOrder', () => {
    it('allows only draft and in_progress', () => {
        expect(canCompleteRepairOrder('draft')).toBe(true);
        expect(canCompleteRepairOrder('in_progress')).toBe(true);
        expect(canCompleteRepairOrder('estimated')).toBe(false);
        expect(canCompleteRepairOrder('completed')).toBe(false);
        expect(canCompleteRepairOrder('cancelled')).toBe(false);
    });
});

describe('canCancelRepairOrder', () => {
    it('allows every active status but not terminal ones', () => {
        expect(canCancelRepairOrder('draft')).toBe(true);
        expect(canCancelRepairOrder('estimated')).toBe(true);
        expect(canCancelRepairOrder('approved')).toBe(true);
        expect(canCancelRepairOrder('in_progress')).toBe(true);
        expect(canCancelRepairOrder('completed')).toBe(false);
        expect(canCancelRepairOrder('cancelled')).toBe(false);
    });
});

describe('formatCents', () => {
    it('renders cents as a two-decimal amount', () => {
        expect(formatCents(0)).toBe('0.00');
        expect(formatCents(12345)).toBe('123.45');
    });

    it('keeps the sign for negative amounts', () => {
        expect(formatCents(-2500)).toBe('-25.00');
    });

    it('rounds fractional cents to two decimals', () => {
        // 1 cent / 100 = 0.01; sub-cent inputs still clamp to two decimals.
        expect(formatCents(1)).toBe('0.01');
    });
});

describe('money input conversion', () => {
    it('shows stored cents as decimal money input text', () => {
        expect(centsToDecimalInput(0)).toBe('0.00');
        expect(centsToDecimalInput(12345)).toBe('123.45');
    });

    it('converts decimal money input to integer cents for submission', () => {
        expect(decimalInputToCents('123.45')).toBe(12345);
        expect(decimalInputToCents('123')).toBe(12300);
        expect(decimalInputToCents('')).toBe(0);
    });
});

describe('vehicleSummary', () => {
    const vehicle = (overrides: Partial<RepairOrderVehicle>): RepairOrderVehicle => ({
        brand: null,
        model: null,
        licensePlate: null,
        ...overrides,
    });

    it('returns the empty label when there is no vehicle', () => {
        expect(vehicleSummary(null)).toBe('No vehicle');
        expect(vehicleSummary(null, 'None')).toBe('None');
    });

    it('joins present parts and skips missing ones', () => {
        expect(vehicleSummary(vehicle({ brand: 'Opel', model: 'Insignia', licensePlate: 'AB123CD' }))).toBe('Opel Insignia AB123CD');
        expect(vehicleSummary(vehicle({ brand: 'Opel', model: null, licensePlate: 'AB123CD' }))).toBe('Opel AB123CD');
    });

    it('returns the empty label when all parts are missing', () => {
        expect(vehicleSummary(vehicle({}))).toBe('No vehicle');
    });
});

describe('formatDate', () => {
    it('returns a dash for null', () => {
        expect(formatDate(null)).toBe('-');
    });

    it('formats an ISO date string', () => {
        // Uses local-date construction, so the day never drifts across zones.
        expect(formatDate('2026-07-03')).toBe('Jul 3, 2026');
    });
});

describe('formatDateTime', () => {
    it('returns a dash for null', () => {
        expect(formatDateTime(null)).toBe('-');
    });
});
