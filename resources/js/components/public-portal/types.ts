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

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};
