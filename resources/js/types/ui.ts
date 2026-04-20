export type Appearance = 'light' | 'dark' | 'system';
export type ResolvedAppearance = 'light' | 'dark';

export type AppShellVariant = 'header' | 'sidebar';

export interface RoleInfo {
    id: number;
    name: string;
    display_name: string;
    description: string | null;
    color: string;
    is_system: boolean;
    is_active: boolean;
    display_order: number;
    created_at?: string;
    updated_at?: string;
}

export interface UserInfo {
    id: number;
    name: string;
    first_name: string;
    last_name: string;
    identification: string | null;
    phone: string | null;
    email: string;
    role: string | RoleInfo;
    birth_date: string | null;
    hire_date: string | null;
    photo_path: string | null;
    photo_url: string | null;
    area_id: number | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface AbsenceType {
    id: number;
    name: string;
    counts_as_hours: boolean;
    deducts_vacation: boolean;
    default_include_saturday: boolean;
    default_include_sunday: boolean;
    default_include_holidays: boolean;
}

export interface Absence {
    id: number;
    user_id: number;
    user: UserInfo;
    absence_type_id: number;
    type: AbsenceType;
    start_datetime: string;
    end_datetime: string;
    include_saturday: boolean;
    include_sunday: boolean;
    include_holidays: boolean;
    holiday_country: string | null;
    total_days: number;
    total_hours: number;
    status: string;
    approved_by: number | null;
    approved_at: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
}
