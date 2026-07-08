export type CustomerActiveWorkshop = {
    id: number;
    name: string;
    slug: string;
};

export type CustomerBookingRequestStatusValue = 'new' | 'confirmed' | 'rejected' | 'cancelled';

export type CustomerBookingRequestStatus = {
    value: CustomerBookingRequestStatusValue;
    label: string;
};

export type CustomerListItem = {
    id: number;
    name: string | null;
    phone: string;
    vehiclesCount: number;
    bookingRequestsCount: number;
    latestBookingRequestDate: string | null;
};

export type CustomerVehicle = {
    id: number;
    brand: string | null;
    model: string | null;
    year: number | null;
    licensePlate: string | null;
};

export type CustomerBookingRequest = {
    id: number;
    status: CustomerBookingRequestStatus;
    problemDescription: string;
    preferredDate: string | null;
    createdAt: string;
    showUrl: string;
};

export type CustomerRepairOrder = {
    id: number;
    status: {
        value: 'draft' | 'estimated' | 'in_progress' | 'completed' | 'cancelled';
        label: string;
    };
    problemDescription: string | null;
    vehicle: CustomerVehicle | null;
    openedAt: string;
    createdAt: string;
    showUrl: string;
};

export type CustomerDetails = {
    id: number;
    name: string | null;
    phone: string;
    createdAt: string;
    vehicles: CustomerVehicle[];
    bookingRequests: CustomerBookingRequest[];
    repairOrders: CustomerRepairOrder[];
};

export type CustomerIndexProps = {
    activeWorkshop: CustomerActiveWorkshop;
    customers: CustomerListItem[];
    filters: {
        search: string;
    };
};

export type CustomerShowProps = {
    activeWorkshop: CustomerActiveWorkshop;
    customer: CustomerDetails;
    flash?: {
        status?: string | null;
    };
    errors?: Record<string, string>;
};
