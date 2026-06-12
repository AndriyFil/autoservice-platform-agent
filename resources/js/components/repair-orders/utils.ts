import type { RepairOrderStatusValue, RepairOrderVehicle } from './types';

export const canCompleteRepairOrder = (status: RepairOrderStatusValue) => status === 'open';

export const canCancelRepairOrder = (status: RepairOrderStatusValue) => status === 'open';

export const vehicleSummary = (vehicle: RepairOrderVehicle | null): string => {
    if (!vehicle) {
        return 'No vehicle';
    }

    const parts = [vehicle.brand, vehicle.model, vehicle.licensePlate].filter(Boolean);

    return parts.length > 0 ? parts.join(' ') : 'No vehicle';
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
