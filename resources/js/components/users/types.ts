export interface ManagedUser {
    id: number;
    name: string;
    first_name: string;
    last_name: string;
    identification: string | null;
    gender: string | null;
    phone: string | null;
    email: string;
    role: string;
    is_active: boolean;
    birth_date: string | null;
    hire_date: string | null;
    photo_path: string | null;
    photo_url?: string;
    allocated: number;
    used: number;
    available: number;
    area_id: number | null;
    area_name: string | null;
}

export interface UserArea {
    id: number;
    name: string;
    color: string;
}

export interface UserRole {
    id: number;
    name: string;
    display_name: string;
    description: string | null;
    color: string;
    is_system: boolean;
    is_active: boolean;
    display_order: number;
    user_count: number;
}

export type UserFormMode = 'create' | 'edit' | 'view';

export type ValidationErrors = Record<string, string>;

/** Convierte la respuesta 422 de Laravel en { campo: primer mensaje }. */
export function extractValidationErrors(error: any): ValidationErrors {
    const raw = error?.response?.data?.errors;
    if (!raw || typeof raw !== 'object') return {};
    return Object.fromEntries(
        Object.entries(raw).map(([key, value]) => [
            key,
            Array.isArray(value) ? String(value[0]) : String(value),
        ]),
    );
}

export function formatDate(date: string | null | undefined): string {
    if (!date) return '—';
    const d = new Date(date.length <= 10 ? `${date}T00:00:00` : date);
    if (Number.isNaN(d.getTime())) return '—';
    return d.toLocaleDateString('es-CO', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

export function getInitials(name: string | null | undefined): string {
    const parts = String(name ?? '')
        .trim()
        .split(/\s+/)
        .filter(Boolean);
    if (parts.length === 0) return 'U';
    if (parts.length === 1) return parts[0][0]?.toUpperCase() ?? 'U';
    return `${parts[0][0] ?? ''}${parts[1][0] ?? ''}`.toUpperCase();
}

export function roleLabel(role: string, roles: UserRole[] = []): string {
    const found = roles.find((r) => r.name === role);
    if (found) return found.display_name;
    if (role === 'admin') return 'Administrador';
    if (role === 'superadmin') return 'Superadministrador';
    if (role === 'colaborador') return 'Colaborador';
    return role;
}
