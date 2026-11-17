export interface UserOption {
    id: number;
    name: string;
    email: string;
    area_id: number | null;
}

export interface AreaOption {
    id: number;
    name: string;
}

export interface TemplateOption {
    id: number;
    title: string;
    area_id: number | null;
    template_content?: string | null;
}

export interface TemplateVariable {
    key: string;
    label: string;
}

export type DocumentMode = 'template' | 'derived' | 'user';
export type SignatureStatus = 'not_required' | 'pending' | 'signed' | 'rejected';

export interface DocumentRow {
    id: number;
    title: string;
    document_type: string;
    document_mode: DocumentMode;
    status: string;
    user: { id: number; name: string; email: string } | null;
    template: { id: number; title: string } | null;
    area: { id: number; name: string } | null;
    uploader: { id: number; name: string } | null;
    signer: { id: number; name: string; email: string } | null;
    signature_status: SignatureStatus;
    signature_method: 'draw' | 'type' | null;
    signature_name: string | null;
    signature_data: string | null;
    signature_requested_at: string | null;
    signed_at: string | null;
    rejected_at: string | null;
    rejection_reason: string | null;
    is_personal_upload: boolean;
    start_date: string | null;
    end_date: string | null;
    expires_at: string | null;
    notes: string | null;
    template_content: string | null;
    rendered_content: string | null;
    original_name: string;
    mime_type: string;
    file_size: number;
    download_url: string;
    created_at: string;
}

export interface DocumentAudit {
    id: number;
    action: string;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    user: { id: number; name: string; email: string } | null;
    created_at: string;
}

export interface DocumentPermissions {
    is_admin: boolean;
    is_superadmin: boolean;
    can_manage_templates: boolean;
    can_sign: boolean;
    max_personal_uploads: number;
}

export interface DocumentFormState {
    title: string;
    document_type: string;
    document_mode: DocumentMode;
    status: string;
    user_id: string;
    template_id: string;
    area_id: string;
    start_date: string;
    end_date: string;
    expires_at: string;
    notes: string;
    template_content: string;
    file: File | null;
}

export interface DocumentFilters {
    search: string;
    user_id: string;
    document_type: string;
    document_mode: string;
    signature_status: string;
}

export const emptyDocumentForm = (): DocumentFormState => ({
    title: '',
    document_type: 'contrato',
    document_mode: 'user',
    status: 'pendiente',
    user_id: '',
    template_id: '',
    area_id: '',
    start_date: '',
    end_date: '',
    expires_at: '',
    notes: '',
    template_content: '',
    file: null,
});

export const DOCUMENT_TYPES: Array<{ value: string; label: string }> = [
    { value: 'contrato', label: 'Contrato' },
    { value: 'politica', label: 'Política' },
    { value: 'certificacion', label: 'Certificación' },
    { value: 'anexo', label: 'Anexo' },
    { value: 'otro', label: 'Otro' },
];

export const DOCUMENT_STATUSES: Array<{ value: string; label: string }> = [
    { value: 'pendiente', label: 'Pendiente' },
    { value: 'firmado', label: 'Firmado' },
    { value: 'rechazado', label: 'Rechazado' },
    { value: 'borrador', label: 'Borrador' },
    { value: 'activo', label: 'Activo' },
    { value: 'vencido', label: 'Vencido' },
];

export const documentTypeLabel = (type: string): string =>
    DOCUMENT_TYPES.find((t) => t.value === type)?.label ??
    (type ? type.charAt(0).toUpperCase() + type.slice(1) : '—');

export const formatDocDate = (value: string | null, includeTime = false) =>
    value
        ? new Date(value).toLocaleDateString('es-CO', {
              year: 'numeric',
              month: 'short',
              day: 'numeric',
              ...(includeTime ? { hour: '2-digit', minute: '2-digit' } : {}),
          })
        : '—';

export const formatFileSize = (bytes: number) => {
    if (!bytes && bytes !== 0) return '—';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

export const auditActionLabel = (action: string) => {
    switch (action) {
        case 'created':
            return 'Creación';
        case 'updated':
            return 'Edición';
        case 'downloaded':
            return 'Descarga';
        case 'deleted':
            return 'Eliminación';
        case 'signature_requested':
            return 'Solicitud de firma';
        case 'signed':
            return 'Documento firmado';
        case 'rejected':
            return 'Documento rechazado';
        default:
            return action;
    }
};

export const modeLabel = (mode: DocumentMode) => {
    if (mode === 'template') return 'General (plantilla)';
    if (mode === 'derived') return 'Derivado';
    return 'Por usuario';
};

/** Etiqueta + clave de estado (para StatusBadge) del estado de firma. */
export const signatureBadge = (
    status: SignatureStatus,
): { status: string; label: string } => {
    if (status === 'pending') return { status: 'pendiente', label: 'Firma pendiente' };
    if (status === 'signed') return { status: 'firmado', label: 'Firmado' };
    if (status === 'rejected') return { status: 'rechazado', label: 'Rechazado' };
    return { status: 'borrador', label: 'Sin firma' };
};
