export type RepairOrderActiveWorkshop = {
    id: number;
    name: string;
    slug: string;
};

export type RepairOrderFormVehicle = {
    id: number;
    brand: string | null;
    model: string | null;
    year: number | null;
    licensePlate: string | null;
};

export type RepairOrderFormCustomer = {
    id: number;
    name: string | null;
    phone: string;
    phoneNormalized: string;
    vehicles?: RepairOrderFormVehicle[];
};

export type RepairOrderStatusValue = 'draft' | 'in_progress' | 'completed' | 'cancelled';

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
    availableStatusTransitions: RepairOrderStatusTransition[];
    vehicle: RepairOrderVehicle | null;
    openedAt: string;
    closedAt: string | null;
};

export type RepairOrderCustomer = {
    id: number;
    name: string | null;
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

export type RepairOrderEstimateDocument = {
    id: number;
    filename: string;
    downloadUrl: string;
    viewUrl: string;
};

export type RepairOrderDocument = {
    id: number;
    filename: string;
    type: {
        value: string;
        label: string;
    };
    status: {
        value: 'generated' | 'failed' | 'archived';
        label: string;
    };
    generatedAt: string | null;
    downloadUrl: string | null;
};

export type RepairOrderEstimate = {
    id: number;
    version: number;
    status: {
        value: 'draft' | 'generated' | 'approved' | 'rejected' | 'superseded' | 'cancelled';
        label: string;
    };
    subtotalCents: number;
    taxCents: number;
    totalCents: number;
    currency: string;
    generatedAt: string | null;
    document: RepairOrderEstimateDocument | null;
};

export type RepairOrderStatusActions = {
    canGenerateEstimate: boolean;
    hasEstimate: boolean;
};

export type RepairOrderStatusTransition = {
    value: RepairOrderStatusValue;
    label: string;
};

export type RepairOrderDetails = {
    id: number;
    status: RepairOrderStatus;
    problemDescription: string;
    notes: string | null;
    requiresEstimateApproval: boolean;
    canUpdateEstimateApprovalRequirement: boolean;
    openedAt: string;
    closedAt: string | null;
    lines: RepairOrderLine[];
    workingTotals: RepairOrderEstimateTotals;
    estimateTotals: RepairOrderEstimateTotals;
    estimates: RepairOrderEstimate[];
    documents: RepairOrderDocument[];
    availableLineTypes: RepairOrderLineTypeOption[];
    statusActions: RepairOrderStatusActions;
    availableStatusTransitions: RepairOrderStatusTransition[];
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
        requires_estimate_approval?: string;
    };
};
