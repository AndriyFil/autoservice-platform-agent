export type WorkshopOption = {
    id: number;
    name: string;
};

export type PublicIntakeVehicle = {
    brand: string;
    model: string;
    year: number | null;
    license_plate: string;
};

export type PublicIntakePayload = {
    message: string;
    phone: string;
    customer_name: string;
    vehicle: PublicIntakeVehicle;
    workshop_id: number | null;
    website: string;
};
