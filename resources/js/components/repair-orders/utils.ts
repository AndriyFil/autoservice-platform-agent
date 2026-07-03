import type { RepairOrderStatusValue, RepairOrderVehicle } from './types';

export const canCompleteRepairOrder = (status: RepairOrderStatusValue) =>
    ['draft', 'in_progress'].includes(status);

export const canCancelRepairOrder = (status: RepairOrderStatusValue) =>
    ['draft', 'estimated', 'approved', 'in_progress'].includes(status);

export const formatCents = (cents: number): string =>
    new Intl.NumberFormat(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(cents / 100);

export const vehicleSummary = (vehicle: RepairOrderVehicle | null, emptyLabel = 'No vehicle'): string => {
    if (!vehicle) {
        return emptyLabel;
    }

    const parts = [vehicle.brand, vehicle.model, vehicle.licensePlate].filter(Boolean);

    return parts.length > 0 ? parts.join(' ') : emptyLabel;
};

export const formatDate = (date: string | null): string => {
    if (!date) {
        return '-';
    }

    const [year, month, day] = date.split('-').map(Number);

    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(new Date(year, month - 1, day));
};

export const formatDateTime = (date: string | null): string => {
    if (!date) {
        return '-';
    }

    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(date));
};
