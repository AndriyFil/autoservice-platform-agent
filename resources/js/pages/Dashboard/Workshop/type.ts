export type WorkshopRoleValue = 'owner' | 'staff';

export type RoleOption = {
    value: WorkshopRoleValue;
    label: string;
};

export type ActiveWorkshop = {
    id: number;
    name: string;
    slug: string;
};

export type WorkshopSettings = {
    id: number;
    name: string;
    slug: string;
    publicIntakePath: string;
    publicIntakeUrl: string;
    createdAt: string;
};

export type StaffMember = {
    id: number;
    userId: number;
    name: string;
    email: string;
    role: RoleOption;
    joinedAt: string;
    isCurrentUser: boolean;
    isLastOwner: boolean;
};

export type WorkshopSettingsPageProps = {
    activeWorkshop: ActiveWorkshop;
    workshop: WorkshopSettings;
    staffMembers: StaffMember[];
    roleOptions: RoleOption[];
    flash?: {
        status?: string | null;
    };
};
