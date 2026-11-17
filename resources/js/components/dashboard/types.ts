export type Role = 'colaborador' | 'admin' | 'superadmin';

export interface Viewer {
    id: number;
    name: string;
    role: Role;
    is_admin: boolean;
    is_superadmin: boolean;
}

export interface MetricPersonal {
    available_vacation_days: number;
    used_vacation_days: number;
    pending_absences: number;
    approved_this_month: number;
    upcoming_expirations: number;
}

export interface MetricOrganization {
    total_employees: number;
    pending_absences: number;
    approved_this_month: number;
    total_vacation_days: number;
    upcoming_expirations: number;
    unavailable_now: number;
}

export interface MetricSuperadmin {
    total_admins: number;
    total_superadmins: number;
    active_tenants: number;
    active_subscriptions: number;
    subscriptions_expiring_30d: number;
}

export interface UserItem {
    id: number;
    name: string;
    identification: string;
    email: string;
}

export interface AreaItem {
    id: number;
    name: string;
    color: string;
}

export interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export interface EmployeeStatusItem {
    id: number;
    name: string;
    status: 'green' | 'yellow' | 'red';
    current_absence: { type: string; end: string } | null;
    upcoming_absence: { type: string; start: string } | null;
}

export interface AbsenceItem {
    id: number;
    user: { id: number; name: string };
    type: { name: string | null };
    start: string;
    end: string;
    days: number;
    requested_at?: string;
}

export interface VacationBalanceItem {
    id: number;
    name: string;
    available: number;
    expiring_soon: { year: number; days: number; expires: string }[];
}

export interface ExpiringVacationItem {
    id: number;
    year: number;
    available_days: number;
    expires_at: string;
}

export interface DashboardResponse {
    viewer: Viewer;
    metrics: {
        personal: MetricPersonal;
        organization: MetricOrganization | null;
        superadmin: MetricSuperadmin | null;
    };
    filters: {
        selected_user_id: number | null;
        selected_area_id: number | null;
        users: UserItem[];
        areas: AreaItem[];
    };
    tables: {
        employee_statuses: {
            data: EmployeeStatusItem[];
            meta: PaginationMeta;
            summary: {
                green: number;
                yellow: number;
                red: number;
                total: number;
            };
            search: string;
        };
        recent_absences: {
            data: AbsenceItem[];
            meta: PaginationMeta;
            search: string;
        };
        pending_approvals: {
            data: AbsenceItem[];
            meta: PaginationMeta;
            search: string;
        };
        vacation_balances: {
            data: VacationBalanceItem[];
            meta: PaginationMeta;
            search: string;
        } | null;
        my_expiring_vacations: ExpiringVacationItem[] | null;
    };
    subscription: {
        has_subscription: boolean;
        show_ad: boolean;
        plan_name?: string;
        days_remaining?: number;
        expires_at?: string;
    } | null;
}
