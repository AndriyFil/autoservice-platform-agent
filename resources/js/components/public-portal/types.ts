export type CustomerRequestStatus = {
    value: 'new' | 'confirmed' | 'rejected' | 'cancelled';
    label: string;
};

export type CustomerRequestSummary = {
    id: number;
    title: string;
    status: CustomerRequestStatus;
    workshopName: string;
    submittedAt: string;
    updatedAt: string;
};

export type CustomerRequestDetail = CustomerRequestSummary & {
    problemDescription: string | null;
    customerName: string | null;
    vehicle: Partial<{ brand: string; model: string; year: number; licensePlate: string }>;
    repairOrder: {
        id: number;
        status: {
            value: 'draft' | 'in_progress' | 'completed' | 'cancelled';
            label: string;
        };
        openedAt: string;
        updatedAt: string;
    } | null;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};
