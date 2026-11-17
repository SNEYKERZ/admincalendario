export interface AbsenceReportRow {
    id: number;
    empleado: string;
    area: string | null;
    tipo: string;
    inicio: string;
    fin: string;
    dias: number;
    horas: number;
    estado: string;
    aprobado_por: string | null;
    aprobado_en: string | null;
}

export type ReportScope = 'personal' | 'area';

export interface FilterOption {
    id: number;
    name: string;
}

export const formatReportDate = (date: string) =>
    date
        ? new Date(date).toLocaleDateString('es-CO', {
              day: '2-digit',
              month: 'short',
              year: 'numeric',
          })
        : '—';

export const formatNumber = (value: number, digits = 1) =>
    Number.isFinite(value)
        ? value.toLocaleString('es-CO', { maximumFractionDigits: digits })
        : '0';
