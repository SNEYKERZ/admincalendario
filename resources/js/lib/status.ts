/**
 * Mapa único de estados → tono semántico + etiqueta en español.
 * Úsalo vía <StatusBadge :status="..." /> para mantener colores coherentes:
 * pendiente = warning (ámbar), aprobado = success (esmeralda),
 * rechazado = destructive (rosa/rojo), cancelado/borrador = neutral (gris),
 * informativo = info (azul cielo).
 */
export type StatusTone =
    | 'neutral'
    | 'primary'
    | 'success'
    | 'warning'
    | 'destructive'
    | 'info';

type StatusDef = { label: string; tone: StatusTone };

const STATUS_MAP: Record<string, StatusDef> = {
    // Ausencias / solicitudes / horas extra
    pendiente: { label: 'Pendiente', tone: 'warning' },
    pending: { label: 'Pendiente', tone: 'warning' },
    en_revision: { label: 'En revisión', tone: 'info' },
    in_review: { label: 'En revisión', tone: 'info' },
    aprobado: { label: 'Aprobado', tone: 'success' },
    aprobada: { label: 'Aprobada', tone: 'success' },
    approved: { label: 'Aprobado', tone: 'success' },
    rechazado: { label: 'Rechazado', tone: 'destructive' },
    rechazada: { label: 'Rechazada', tone: 'destructive' },
    rejected: { label: 'Rechazado', tone: 'destructive' },
    cancelado: { label: 'Cancelado', tone: 'neutral' },
    cancelada: { label: 'Cancelada', tone: 'neutral' },
    cancelled: { label: 'Cancelado', tone: 'neutral' },
    canceled: { label: 'Cancelado', tone: 'neutral' },
    borrador: { label: 'Borrador', tone: 'neutral' },
    draft: { label: 'Borrador', tone: 'neutral' },
    // Documentos
    firmado: { label: 'Firmado', tone: 'success' },
    signed: { label: 'Firmado', tone: 'success' },
    enviado: { label: 'Enviado', tone: 'info' },
    sent: { label: 'Enviado', tone: 'info' },
    vencido: { label: 'Vencido', tone: 'destructive' },
    expired: { label: 'Vencido', tone: 'destructive' },
    // Disponibilidad / cuentas
    disponible: { label: 'Disponible', tone: 'success' },
    available: { label: 'Disponible', tone: 'success' },
    ausente: { label: 'Ausente', tone: 'destructive' },
    absent: { label: 'Ausente', tone: 'destructive' },
    proximo: { label: 'Próxima ausencia', tone: 'warning' },
    upcoming: { label: 'Próxima ausencia', tone: 'warning' },
    activo: { label: 'Activo', tone: 'success' },
    active: { label: 'Activo', tone: 'success' },
    inactivo: { label: 'Inactivo', tone: 'neutral' },
    inactive: { label: 'Inactivo', tone: 'neutral' },
    trial: { label: 'Prueba', tone: 'info' },
    suspended: { label: 'Suspendido', tone: 'destructive' },
    suspendido: { label: 'Suspendido', tone: 'destructive' },
    // Semáforos legados
    green: { label: 'Disponible', tone: 'success' },
    yellow: { label: 'Próxima ausencia', tone: 'warning' },
    red: { label: 'Ausente', tone: 'destructive' },
};

export const STATUS_TONE_CLASSES: Record<StatusTone, string> = {
    neutral: 'bg-muted text-muted-foreground border-border',
    primary: 'bg-primary-soft text-primary border-primary/20',
    success: 'bg-success-soft text-success border-success/20',
    warning: 'bg-warning-soft text-warning border-warning/25',
    destructive: 'bg-destructive-soft text-destructive border-destructive/20',
    info: 'bg-info-soft text-info border-info/20',
};

export const STATUS_DOT_CLASSES: Record<StatusTone, string> = {
    neutral: 'bg-muted-foreground/60',
    primary: 'bg-primary',
    success: 'bg-success',
    warning: 'bg-warning',
    destructive: 'bg-destructive',
    info: 'bg-info',
};

export function normalizeStatus(status?: string | null): string {
    return String(status ?? '')
        .trim()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .replace(/\s+/g, '_');
}

export function getStatusDef(status?: string | null): StatusDef {
    const key = normalizeStatus(status);
    if (STATUS_MAP[key]) return STATUS_MAP[key];
    const raw = String(status ?? '').trim();
    return {
        label: raw
            ? raw.charAt(0).toUpperCase() + raw.slice(1).replace(/_/g, ' ')
            : '—',
        tone: 'neutral',
    };
}

export function getStatusLabel(status?: string | null): string {
    return getStatusDef(status).label;
}

export function getStatusTone(status?: string | null): StatusTone {
    return getStatusDef(status).tone;
}

/** Color CSS (var) para pintar eventos de calendario según estado. */
export function getStatusCssColor(status?: string | null): string {
    const tone = getStatusTone(status);
    return tone === 'neutral' ? 'var(--muted-foreground)' : `var(--${tone})`;
}
