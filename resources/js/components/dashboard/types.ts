export type DashboardActiveWorkshop = {
    id: number;
    name: string;
    slug: string;
};

export type DashboardBookingRequestStatusValue = 'new' | 'confirmed' | 'rejected' | 'cancelled';

export type DashboardBookingRequestStatus = {
    value: DashboardBookingRequestStatusValue;
    label: string;
};

export type DashboardBookingRequestVehicle = {
    brand: string | null;
    model: string | null;
    licensePlate: string | null;
};

export type DashboardBookingRequest = {
    id: number;
    customerName: string;
    customerPhone: string;
    problemDescription: string;
    preferredDate: string | null;
    status: DashboardBookingRequestStatus;
    vehicle: DashboardBookingRequestVehicle | null;
    createdAt: string;
};

export type DashboardFlash = {
    status?: string | null;
};

export type DashboardErrors = {
    status?: string;
};

export type DashboardProps = {
    activeWorkshop: DashboardActiveWorkshop;
    bookingRequests: DashboardBookingRequest[];
    flash?: DashboardFlash;
    errors?: DashboardErrors;
};

export type StatusAction = 'confirmed' | 'rejected' | 'cancelled';

export type PendingStatusChange = {
    bookingRequestId: number;
    customerName: string;
    status: StatusAction;
    label: string;
    description: string;
    confirmButtonClass: string;
};
