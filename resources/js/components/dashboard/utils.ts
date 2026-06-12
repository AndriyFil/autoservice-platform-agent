import type { DashboardBookingRequestStatusValue, DashboardBookingRequestVehicle, StatusAction } from './types';

export const canConfirmBookingRequest = (status: DashboardBookingRequestStatusValue) => status === 'new';

export const canRejectBookingRequest = (status: DashboardBookingRequestStatusValue) => status === 'new';

export const canCancelBookingRequest = (status: DashboardBookingRequestStatusValue) => ['new', 'confirmed'].includes(status);

export const statusActionDetails = (status: StatusAction) =>
    ({
        confirmed: {
            label: 'Confirm request',
            description: 'This marks the request as confirmed. You can still cancel it later if needed.',
            confirmButtonClass: 'bg-green-600 text-white hover:bg-green-700',
        },
        rejected: {
            label: 'Reject request',
            description: 'This marks the request as rejected. There is no valid transition back from rejected.',
            confirmButtonClass: '',
        },
        cancelled: {
            label: 'Cancel request',
            description: 'This marks the request as cancelled. Use this when the request should no longer continue.',
            confirmButtonClass: 'bg-amber-600 text-white hover:bg-amber-700',
        },
    })[status];

export const vehicleSummary = (vehicle: DashboardBookingRequestVehicle | null): string => {
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

export const formatDateTime = (date: string): string =>
    new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(date));
