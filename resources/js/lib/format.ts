const numberFormat = new Intl.NumberFormat('es-CO', {
    maximumFractionDigits: 2,
});

const toNumber = (value: number | string | null | undefined): number | null => {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const parsed = typeof value === 'number' ? value : Number.parseFloat(value);

    return Number.isFinite(parsed) ? parsed : null;
};

/** "1 día", "2,5 días", "—" si no hay valor. */
export function formatDays(value: number | string | null | undefined): string {
    const days = toNumber(value);

    if (days === null) {
        return '—';
    }

    return `${numberFormat.format(days)} ${days === 1 ? 'día' : 'días'}`;
}

/** "1 hora", "4 horas", "—" si no hay valor. */
export function formatHours(value: number | string | null | undefined): string {
    const hours = toNumber(value);

    if (hours === null) {
        return '—';
    }

    return `${numberFormat.format(hours)} ${hours === 1 ? 'hora' : 'horas'}`;
}

/** Duración de una ausencia: días si hay al menos uno, si no las horas. */
export function formatAbsenceDuration(
    days: number | string | null | undefined,
    hours?: number | string | null,
): string {
    const d = toNumber(days);

    if (d !== null && d >= 1) {
        return formatDays(d);
    }

    const h = toNumber(hours);

    if (h !== null && h > 0) {
        return formatHours(h);
    }

    return formatDays(d);
}
