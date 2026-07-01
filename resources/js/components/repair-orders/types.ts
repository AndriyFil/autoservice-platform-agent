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

export type RepairOrderStatusValue = 'draft' | 'estimated' | 'approved' | 'in_progress' | 'completed' | 'cancelled';

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
    problemDescription: string;
    originalMessage: string | null;
    preferredDate: string | null;
    createdAt: string;
};

export type RepairOrderLineTypeOption = {
    value: 'labor' | 'part' | 'fee' | 'discount';
    label: string;
};

export type RepairOrderLine = {
    id: number;
    type: RepairOrderLineTypeOption;
    description: string;
    quantity: string;
    unitPriceCents: number;
    taxRate: string;
    sortOrder: number;
    subtotalCents: number;
    taxCents: number;
    totalCents: number;
};

export type RepairOrderEstimateTotals = {
    subtotalCents: number;
    taxCents: number;
    totalCents: number;
};

export type RepairOrderStatusActions = {
    canMarkEstimated: boolean;
    canComplete: boolean;
    canCancel: boolean;
};

export type RepairOrderDetails = {
    id: number;
    status: RepairOrderStatus;
    problemDescription: string;
    notes: string | null;
    openedAt: string;
    closedAt: string | null;
    lines: RepairOrderLine[];
    estimateTotals: RepairOrderEstimateTotals;
    availableLineTypes: RepairOrderLineTypeOption[];
    statusActions: RepairOrderStatusActions;
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
        repair_order_line?: string;
    };
};
