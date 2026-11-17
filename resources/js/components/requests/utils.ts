export type RequestApproval = {
    id: number;
    status: string;
    comment: string | null;
    decided_at: string;
    approver_name: string;
};

export const REQUEST_TYPES: Record<string, string> = {
    certificado: 'Certificado laboral',
    permiso_especial: 'Permiso especial',
    documento: 'Documento',
    cambio_datos: 'Cambio de datos',
    otro: 'Otra solicitud',
};

export const REQUEST_STATUSES = [
    { value: '', label: 'Todas' },
    { value: 'pendiente', label: 'Pendientes' },
    { value: 'aprobado', label: 'Aprobadas' },
    { value: 'rechazado', label: 'Rechazadas' },
];

export const getTypeLabel = (type: string): string =>
    REQUEST_TYPES[type] || type;

export const formatRequestDate = (date: string, withTime = false): string => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        ...(withTime ? { hour: '2-digit', minute: '2-digit' } : {}),
    });
};
