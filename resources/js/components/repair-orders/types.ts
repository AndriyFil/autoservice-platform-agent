export type RepairOrderActiveWorkshop = {
    id: number;
    name: string;
    slug: string;
};

export type RepairOrderFormVehicle = {
    id: number;
    brand: string | null;
    model: string | null;
    licensePlate: string | null;
};

export type RepairOrderFormCustomer = {
    id: number;
    name: string;
    phone: string;
    vehicles?: RepairOrderFormVehicle[];
};

export type RepairOrderStatusValue = 'open' | 'completed' | 'cancelled';

export type RepairOrderStatus = {
    value: RepairOrderStatusValue;
    label: string;
};

export type RepairOrderVehicle = {
    brand: string | null;
    model: string | null;
    licensePlate: string | null;
};

export type RepairOrderListItem = {
    id: number;
    customerName: string;
    problemDescription: string;
    status: RepairOrderStatus;
    vehicle: RepairOrderVehicle | null;
    openedAt: string;
    closedAt: string | null;
};

export type RepairOrderCustomer = {
    id: number;
    name: string;
    phone: string;
};

export type RepairOrderDetailsVehicle = RepairOrderVehicle & {
    id: number;
};

export type RepairOrderSourceBookingRequest = {
    id: number;
    status: {
        value: 'new' | 'confirmed' | 'rejected' | 'cancelled';
        label: string;
    };
    preferredDate: string | null;
    createdAt: string;
};

export type RepairOrderDetails = {
    id: number;
    status: RepairOrderStatus;
    problemDescription: string;
    openedAt: string;
    closedAt: string | null;
    customer: RepairOrderCustomer;
    vehicle: RepairOrderDetailsVehicle | null;
    bookingRequest: RepairOrderSourceBookingRequest | null;
};

export type RepairOrderIndexProps = {
    activeWorkshop: RepairOrderActiveWorkshop;
    repairOrders: RepairOrderListItem[];
    flash?: {
        status?: string | null;
    };
};

export type RepairOrderShowProps = {
    activeWorkshop: RepairOrderActiveWorkshop;
    repairOrder: RepairOrderDetails;
    flash?: {
        status?: string | null;
    };
    errors?: {
        status?: string;
    };
};
