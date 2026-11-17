export type OvertimeRecord = {
    id: number;
    user_id?: number;
    date: string;
    start_time: string;
    end_time: string;
    hours: number | string;
    project?: string | null;
    client?: string | null;
    reason?: string | null;
    status: string;
    approval_notes?: string | null;
    user?: { id: number; name: string; area?: { name: string } | null } | null;
    approver?: { id: number; name: string } | null;
};

export type OvertimeFormValues = {
    date: string;
    start_time: string;
    end_time: string;
    project: string;
    client: string;
    reason: string;
};

/** Diferencia en horas entre dos "HH:mm" (0 si falta alguno). */
export function calcHours(start?: string, end?: string): number {
    if (!start || !end) return 0;
    const [sh, sm] = start.split(':').map(Number);
    const [eh, em] = end.split(':').map(Number);
    return (eh * 60 + em - (sh * 60 + sm)) / 60;
}

/** "2026-11-17T00:00:00.000000Z" | "2026-11-17" → "2026-11-17" */
export function toDateInput(value?: string | null): string {
    return String(value ?? '').substring(0, 10);
}

export function formatDate(value?: string | null): string {
    const ymd = toDateInput(value);
    if (!ymd) return '—';
    const [y, m, d] = ymd.split('-').map(Number);
    return new Date(y, m - 1, d).toLocaleDateString('es-CO', {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

export function formatHours(value: number | string | null | undefined): string {
    const n = Number(value ?? 0);
    return `${Number.isFinite(n) ? n.toFixed(2) : '0.00'} h`;
}

export function formatTimeRange(start?: string, end?: string): string {
    if (!start || !end) return '';
    return `${start.substring(0, 5)} – ${end.substring(0, 5)}`;
}

export const MONTHS = Array.from({ length: 12 }, (_, i) => ({
    value: String(i + 1).padStart(2, '0'),
    label: new Date(2000, i).toLocaleString('es-ES', { month: 'long' }),
}));

export const YEARS = Array.from({ length: 5 }, (_, i) =>
    String(new Date().getFullYear() - i),
);

/** Extrae el primer mensaje de error de una respuesta axios de Laravel. */
export function firstErrorMessage(error: any, fallback: string): string {
    const data = error?.response?.data;
    if (data?.errors) {
        const first = Object.values(data.errors as Record<string, string[]>)[0];
        if (Array.isArray(first) && first[0]) return first[0];
    }
    return data?.message || fallback;
}
