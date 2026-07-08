import type { RepairOrderActiveWorkshop, RepairOrderFormCustomer } from '@/components/repair-orders/types';

export type RepairOrderCreateDefaults = {
    customer_id: string;
    customer_name: string;
    customer_phone: string;
    vehicle_id: string;
    problem_description: string;
    booking_request_id: string;
    requires_estimate_approval: boolean;
};

export type RepairOrderSourceBookingRequest = {
    id: number;
    customerName: string | null;
    customerPhone: string | null;
    problemDescription: string | null;
    originalMessage: string | null;
    preferredDate: string | null;
    existingCustomer: {
        id: number;
        name: string | null;
        phone: string;
    } | null;
};

export type RepairOrderCreateProps = {
    activeWorkshop: RepairOrderActiveWorkshop;
    customers: RepairOrderFormCustomer[];
    defaults: RepairOrderCreateDefaults;
    sourceBookingRequest: RepairOrderSourceBookingRequest | null;
    errors?: {
        repair_order?: string;
    };
};
