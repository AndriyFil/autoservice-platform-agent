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
    name: string;
    phone: string;
    vehiclesCount: number;
    bookingRequestsCount: number;
    latestBookingRequestDate: string | null;
};

export type CustomerVehicle = {
    id: number;
    brand: string | null;
    model: string | null;
    licensePlate: string | null;
};

export type CustomerBookingRequest = {
    id: number;
    status: CustomerBookingRequestStatus;
    problemDescription: string;
    preferredDate: string | null;
    createdAt: string;
};

export type CustomerDetails = {
    id: number;
    name: string;
    phone: string;
    vehicles: CustomerVehicle[];
    bookingRequests: CustomerBookingRequest[];
};

export type CustomerIndexProps = {
    activeWorkshop: CustomerActiveWorkshop;
    customers: CustomerListItem[];
};

export type CustomerShowProps = {
    activeWorkshop: CustomerActiveWorkshop;
    customer: CustomerDetails;
};
