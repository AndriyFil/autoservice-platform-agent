import type { RepairOrderActiveWorkshop, RepairOrderFormCustomer } from '@/components/repair-orders/types';

export type RepairOrderCreateDefaults = {
    customer_id: string;
    vehicle_id: string;
    problem_description: string;
    booking_request_id: string;
};

export type RepairOrderSourceBookingRequest = {
    id: number;
    customerName: string;
    customerPhone: string;
    preferredDate: string | null;
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
