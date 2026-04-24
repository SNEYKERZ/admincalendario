<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { useToast } from 'vue-toastification';
import { includesNormalized, normalizeForSearch } from '@/lib/search';

interface UserOption {
    id: number;
    name: string;
    email: string;
    area_id: number | null;
}

interface AreaOption {
    id: number;
    name: string;
}

interface TemplateOption {
    id: number;
    title: string;
    area_id: number | null;
    template_content?: string | null;
}

interface TemplateVariable {
    key: string;
    label: string;
}

interface DocumentRow {
    id: number;
    title: string;
    document_type: string;
    document_mode: 'template' | 'derived' | 'user';
    status: string;
    user: { id: number; name: string; email: string } | null;
    template: { id: number; title: string } | null;
    area: { id: number; name: string } | null;
    uploader: { id: number; name: string } | null;
    signer: { id: number; name: string; email: string } | null;
    signature_status: 'not_required' | 'pending' | 'signed' | 'rejected';
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

interface DocumentAudit {
    id: number;
    action: string;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    user: { id: number; name: string; email: string } | null;
    created_at: string;
}

interface Permissions {
    is_admin: boolean;
    is_superadmin: boolean;
    can_manage_templates: boolean;
    can_sign: boolean;
    max_personal_uploads: number;
}

const toast = useToast();
const loading = ref(false);
const saving = ref(false);
const submittingSignature = ref(false);
const documents = ref<DocumentRow[]>([]);
const users = ref<UserOption[]>([]);
const areas = ref<AreaOption[]>([]);
const templates = ref<TemplateOption[]>([]);
const templateVariables = ref<TemplateVariable[]>([]);
const currentUserId = ref<number | null>(null);
const permissions = ref<Permissions>({
    is_admin: false,
    is_superadmin: false,
    can_manage_templates: false,
    can_sign: true,
    max_personal_uploads: 5,
});
const filters = ref({
    search: '',
    user_id: 'all',
    document_type: 'all',
    document_mode: 'all',
    signature_status: 'all',
});
const editingId = ref<number | null>(null);
const loadingAuditsFor = ref<number | null>(null);
const selectedAuditDocumentId = ref<number | null>(null);
const audits = ref<Record<number, DocumentAudit[]>>({});
const signingDocument = ref<DocumentRow | null>(null);
const signatureMethod = ref<'draw' | 'type'>('draw');
const signatureName = ref('');
const canvasRef = ref<HTMLCanvasElement | null>(null);
const drawing = ref(false);
const hasDrawn = ref(false);

const metrics = ref({
    total: 0,
    active: 0,
    pending_signature: 0,
    signed: 0,
    rejected: 0,
    expiring_soon: 0,
    expired: 0,
});

const form = ref({
    title: '',
    document_type: 'contrato',
    document_mode: 'user' as 'template' | 'derived' | 'user',
    status: 'pendiente',
    user_id: '',
    template_id: '',
    area_id: '',
    start_date: '',
    end_date: '',
    expires_at: '',
    notes: '',
    template_content: '',
    file: null as File | null,
});

const isAdmin = computed(() => permissions.value.is_admin);
const canManageTemplates = computed(() => permissions.value.can_manage_templates);
const selectedTemplate = computed(() =>
    templates.value.find((template) => String(template.id) === form.value.template_id) ?? null,
);
const selectedTemplateHasContent = computed(
    () => !!selectedTemplate.value?.template_content?.trim(),
);

const personalUploadsCount = computed(
    () =>
        documents.value.filter(
            (doc) => doc.is_personal_upload && doc.uploader?.id === currentUserId.value,
        ).length,
);

const contracts = computed(() =>
    documents.value.filter((doc) => {
        const type = (doc.document_type ?? '').toLowerCase();
        return (
            doc.document_mode === 'template' ||
            doc.document_mode === 'derived' ||
            type === 'contrato'
        );
    }),
);

const availableDocumentTypes = computed(() => {
    const uniqueTypes = new Set(documents.value.map((doc) => doc.document_type));
    return Array.from(uniqueTypes.values()).sort();
});

const filteredDocuments = computed(() => {
    const search = normalizeForSearch(filters.value.search);
    return documents.value.filter((doc) => {
        const searchOk =
            search.length === 0 ||
            includesNormalized(doc.title, search) ||
            includesNormalized(doc.original_name, search) ||
            includesNormalized(doc.user?.name, search);

        const userOk =
            filters.value.user_id === 'all' || String(doc.user?.id ?? '') === filters.value.user_id;
        const typeOk =
            filters.value.document_type === 'all' || doc.document_type === filters.value.document_type;
        const modeOk =
            filters.value.document_mode === 'all' || doc.document_mode === filters.value.document_mode;
        const signatureOk =
            filters.value.signature_status === 'all' ||
            doc.signature_status === filters.value.signature_status;

        return searchOk && userOk && typeOk && modeOk && signatureOk;
    });
});

const selectedAudits = computed(() => {
    if (!selectedAuditDocumentId.value) return [];
    return audits.value[selectedAuditDocumentId.value] ?? [];
});

const formatDate = (value: string | null, includeTime = false) =>
    value
        ? new Date(value).toLocaleDateString('es-CO', {
              year: 'numeric',
              month: 'short',
              day: 'numeric',
              ...(includeTime ? { hour: '2-digit', minute: '2-digit' } : {}),
          })
        : '-';

const formatSize = (bytes: number) => {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

const actionLabel = (action: string) => {
    switch (action) {
        case 'created':
            return 'Creacion';
        case 'updated':
            return 'Edicion';
        case 'downloaded':
            return 'Descarga';
        case 'deleted':
            return 'Eliminacion';
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

const modeLabel = (mode: DocumentRow['document_mode']) => {
    if (mode === 'template') return 'General';
    if (mode === 'derived') return 'Derivado';
    return 'Por usuario';
};

const signatureLabel = (status: DocumentRow['signature_status']) => {
    if (status === 'pending') return 'Pendiente';
    if (status === 'signed') return 'Firmado';
    if (status === 'rejected') return 'Rechazado';
    return 'No requerida';
};

const statusClass = (status: string) => {
    if (status === 'firmado') return 'bg-emerald-100 text-emerald-700';
    if (status === 'pendiente') return 'bg-amber-100 text-amber-700';
    if (status === 'rechazado') return 'bg-rose-100 text-rose-700';
    if (status === 'borrador') return 'bg-slate-100 text-slate-700';
    return 'bg-blue-100 text-blue-700';
};

const resetForm = () => {
    form.value = {
        title: '',
        document_type: 'contrato',
        document_mode: isAdmin.value ? 'user' : 'user',
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
    };
    editingId.value = null;
};

const loadDocuments = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/documents');
        documents.value = response.data.documents ?? [];
        users.value = response.data.users ?? [];
        areas.value = response.data.areas ?? [];
        templates.value = response.data.templates ?? [];
        templateVariables.value = response.data.template_variables ?? [];
        metrics.value = response.data.metrics ?? metrics.value;
        permissions.value = response.data.permissions ?? permissions.value;
        currentUserId.value = response.data.current_user_id ?? null;
    } catch {
        toast.error('No se pudo cargar el modulo de documentos');
    } finally {
        loading.value = false;
    }
};

const loadAudits = async (documentId: number) => {
    loadingAuditsFor.value = documentId;
    selectedAuditDocumentId.value = documentId;
    try {
        const response = await axios.get(`/documents/${documentId}/audits`);
        audits.value[documentId] = response.data.audits ?? [];
    } catch {
        toast.error('No fue posible cargar el historial');
    } finally {
        loadingAuditsFor.value = null;
    }
};

const fillForEdit = (doc: DocumentRow) => {
    editingId.value = doc.id;
    form.value = {
        title: doc.title,
        document_type: doc.document_type,
        document_mode: doc.document_mode,
        status: doc.status,
        user_id: doc.user ? String(doc.user.id) : '',
        template_id: doc.template ? String(doc.template.id) : '',
        area_id: doc.area ? String(doc.area.id) : '',
        start_date: doc.start_date ?? '',
        end_date: doc.end_date ?? '',
        expires_at: doc.expires_at ?? '',
        notes: doc.notes ?? '',
        template_content: doc.template_content ?? '',
        file: null,
    };
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const canEditDocument = (doc: DocumentRow) =>
    isAdmin.value && (doc.document_mode !== 'template' || canManageTemplates.value);

const canDeleteDocument = (doc: DocumentRow) =>
    isAdmin.value && (doc.document_mode !== 'template' || canManageTemplates.value);

const canRequestSignature = (doc: DocumentRow) =>
    isAdmin.value && doc.document_mode !== 'template' && !!doc.user && doc.signature_status !== 'signed';

const canSignDocument = (doc: DocumentRow) => {
    if (doc.signature_status !== 'pending') return false;
    if (isAdmin.value) return doc.document_mode !== 'template';
    return doc.user?.id === currentUserId.value;
};

const insertTemplateVariable = (variableKey: string) => {
    const token = `{{${variableKey}}}`;
    form.value.template_content = `${form.value.template_content}${form.value.template_content ? '\n' : ''}${token}`;
};

const submit = async () => {
    const canUseTemplateContent =
        (isAdmin.value &&
            form.value.document_mode === 'template' &&
            form.value.template_content.trim().length > 0) ||
        (isAdmin.value &&
            form.value.document_mode !== 'template' &&
            form.value.template_id.length > 0 &&
            selectedTemplateHasContent.value);

    if (!editingId.value && !form.value.file && !canUseTemplateContent) {
        toast.error('Debes seleccionar un archivo o usar una plantilla con contenido');
        return;
    }

    if (isAdmin.value && form.value.document_mode !== 'template' && !form.value.user_id) {
        toast.error('Debes seleccionar un colaborador');
        return;
    }

    if (isAdmin.value && form.value.document_mode === 'derived' && !form.value.template_id) {
        toast.error('Debes seleccionar la plantilla base para el documento derivado');
        return;
    }

    saving.value = true;
    try {
        const payload = new FormData();
        payload.append('title', form.value.title);
        payload.append('document_type', form.value.document_type);
        payload.append('document_mode', form.value.document_mode);
        payload.append('status', form.value.status);
        if (form.value.user_id) payload.append('user_id', form.value.user_id);
        if (form.value.template_id) payload.append('template_id', form.value.template_id);
        if (form.value.area_id) payload.append('area_id', form.value.area_id);
        if (form.value.start_date) payload.append('start_date', form.value.start_date);
        if (form.value.end_date) payload.append('end_date', form.value.end_date);
        if (form.value.expires_at) payload.append('expires_at', form.value.expires_at);
        if (form.value.notes) payload.append('notes', form.value.notes);
        if (form.value.template_content.trim()) {
            payload.append('template_content', form.value.template_content.trim());
        }
        if (form.value.file) payload.append('file', form.value.file);

        if (editingId.value) {
            await axios.post(`/documents/${editingId.value}`, payload, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            toast.success('Documento actualizado');
        } else {
            await axios.post('/documents', payload, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            toast.success('Documento creado');
        }

        resetForm();
        await loadDocuments();
    } catch (error: any) {
        if (error?.response?.data?.errors) {
            Object.values(error.response.data.errors)
                .flat()
                .forEach((msg) => toast.error(String(msg)));
        } else {
            toast.error('No se pudo guardar el documento');
        }
    } finally {
        saving.value = false;
    }
};

const requestSignature = async (doc: DocumentRow) => {
    try {
        await axios.post(`/documents/${doc.id}/request-signature`);
        toast.success('Firma solicitada correctamente');
        await loadDocuments();
    } catch {
        toast.error('No fue posible solicitar la firma');
    }
};

const removeDocument = async (id: number) => {
    try {
        await axios.delete(`/documents/${id}`);
        toast.success('Documento eliminado');
        if (selectedAuditDocumentId.value === id) {
            selectedAuditDocumentId.value = null;
        }
        await loadDocuments();
    } catch {
        toast.error('No fue posible eliminar el documento');
    }
};

const openSignaturePanel = async (doc: DocumentRow) => {
    signingDocument.value = doc;
    signatureMethod.value = 'draw';
    signatureName.value = '';
    await nextTick();
    setupCanvas();
};

const closeSignaturePanel = () => {
    signingDocument.value = null;
    hasDrawn.value = false;
    signatureName.value = '';
};

const setupCanvas = () => {
    const canvas = canvasRef.value;
    if (!canvas) return;

    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    const rect = canvas.getBoundingClientRect();
    canvas.width = Math.floor(rect.width * ratio);
    canvas.height = Math.floor(rect.height * ratio);

    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    ctx.scale(ratio, ratio);
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.strokeStyle = '#0f172a';
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, rect.width, rect.height);
    hasDrawn.value = false;
};

const canvasPoint = (event: PointerEvent) => {
    const canvas = canvasRef.value;
    if (!canvas) return { x: 0, y: 0 };
    const rect = canvas.getBoundingClientRect();
    return { x: event.clientX - rect.left, y: event.clientY - rect.top };
};

const startDrawing = (event: PointerEvent) => {
    const canvas = canvasRef.value;
    const ctx = canvas?.getContext('2d');
    if (!canvas || !ctx) return;
    drawing.value = true;
    canvas.setPointerCapture(event.pointerId);
    const { x, y } = canvasPoint(event);
    ctx.beginPath();
    ctx.moveTo(x, y);
};

const draw = (event: PointerEvent) => {
    if (!drawing.value) return;
    const canvas = canvasRef.value;
    const ctx = canvas?.getContext('2d');
    if (!canvas || !ctx) return;
    const { x, y } = canvasPoint(event);
    ctx.lineTo(x, y);
    ctx.stroke();
    hasDrawn.value = true;
};

const endDrawing = (event: PointerEvent) => {
    const canvas = canvasRef.value;
    const ctx = canvas?.getContext('2d');
    if (!canvas || !ctx) return;
    drawing.value = false;
    ctx.closePath();
    if (canvas.hasPointerCapture(event.pointerId)) {
        canvas.releasePointerCapture(event.pointerId);
    }
};

const clearSignature = () => {
    setupCanvas();
};

const submitSignature = async () => {
    const doc = signingDocument.value;
    if (!doc) return;

    let payload: Record<string, string> = {
        signature_method: signatureMethod.value,
    };

    if (signatureMethod.value === 'draw') {
        const data = canvasRef.value?.toDataURL('image/png');
        if (!data || !hasDrawn.value) {
            toast.error('Debes dibujar la firma');
            return;
        }
        payload = { ...payload, signature_data: data };
    } else if (!signatureName.value.trim()) {
        toast.error('Debes escribir tu nombre para firmar');
        return;
    } else {
        payload = { ...payload, signature_name: signatureName.value.trim() };
    }

    submittingSignature.value = true;
    try {
        await axios.post(`/documents/${doc.id}/sign`, payload);
        toast.success('Documento firmado correctamente');
        closeSignaturePanel();
        await loadDocuments();
    } catch {
        toast.error('No fue posible firmar el documento');
    } finally {
        submittingSignature.value = false;
    }
};

const rejectDocument = async (doc: DocumentRow) => {
    const reason = window.prompt('Motivo del rechazo (opcional):', '');
    try {
        await axios.post(`/documents/${doc.id}/reject`, {
            reason: reason ?? '',
        });
        toast.success('Documento rechazado');
        await loadDocuments();
    } catch {
        toast.error('No fue posible rechazar el documento');
    }
};

onMounted(async () => {
    await loadDocuments();
    resetForm();
});
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Contratos y Documentos
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Gestion de plantillas, documentos derivados, asignacion por colaborador y firma digital.
                </p>
                <p
                    v-if="!isAdmin"
                    class="mt-2 text-xs text-gray-500 dark:text-gray-400"
                >
                    Documentos personales: {{ personalUploadsCount }}/{{ permissions.max_personal_uploads }}
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total documentos</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ metrics.total }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Pendientes de firma</p>
                    <p class="text-2xl font-semibold text-amber-600">{{ metrics.pending_signature }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Firmados</p>
                    <p class="text-2xl font-semibold text-emerald-600">{{ metrics.signed }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Rechazados</p>
                    <p class="text-2xl font-semibold text-rose-600">{{ metrics.rejected }}</p>
                </div>
            </div>

            <form
                class="grid gap-4 rounded-xl border border-gray-200 bg-white p-5 md:grid-cols-2 dark:border-gray-700 dark:bg-gray-800"
                @submit.prevent="submit"
            >
                <div class="md:col-span-2 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ editingId ? 'Editar documento' : 'Nuevo documento' }}
                    </h2>
                    <button
                        v-if="editingId"
                        type="button"
                        class="btn-secondary"
                        @click="resetForm"
                    >
                        Cancelar edicion
                    </button>
                </div>

                <div>
                    <label class="label">Titulo</label>
                    <input v-model="form.title" required class="input" />
                </div>

                <div>
                    <label class="label">Tipo de documento</label>
                    <select v-model="form.document_type" class="input">
                        <option value="contrato">Contrato</option>
                        <option value="politica">Politica</option>
                        <option value="certificacion">Certificacion</option>
                        <option value="anexo">Anexo</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>

                <div v-if="isAdmin">
                    <label class="label">Modalidad</label>
                    <select v-model="form.document_mode" class="input">
                        <option value="user">Documento por usuario</option>
                        <option value="derived">Documento derivado</option>
                        <option v-if="canManageTemplates" value="template">Documento general (plantilla)</option>
                    </select>
                </div>

                <div>
                    <label class="label">Estado</label>
                    <select v-model="form.status" class="input">
                        <option value="pendiente">Pendiente</option>
                        <option value="firmado">Firmado</option>
                        <option value="rechazado">Rechazado</option>
                        <option value="borrador">Borrador</option>
                        <option value="activo">Activo</option>
                        <option value="vencido">Vencido</option>
                    </select>
                </div>

                <div v-if="isAdmin && form.document_mode !== 'template'">
                    <label class="label">Colaborador</label>
                    <select v-model="form.user_id" class="input" required>
                        <option value="">Selecciona un colaborador</option>
                        <option v-for="user in users" :key="user.id" :value="String(user.id)">
                            {{ user.name }}
                        </option>
                    </select>
                </div>

                <div v-if="isAdmin && form.document_mode !== 'template'">
                    <label class="label">
                        {{ form.document_mode === 'derived' ? 'Plantilla base (requerida)' : 'Plantilla usada (opcional)' }}
                    </label>
                    <select v-model="form.template_id" class="input">
                        <option value="">
                            {{ form.document_mode === 'derived' ? 'Selecciona una plantilla' : 'Sin plantilla relacionada' }}
                        </option>
                        <option v-for="template in templates" :key="template.id" :value="String(template.id)">
                            {{ template.title }}
                        </option>
                    </select>
                    <p
                        v-if="form.template_id"
                        class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                    >
                        {{
                            selectedTemplateHasContent
                                ? 'Esta plantilla tiene variables y se autollenará al derivar para el colaborador.'
                                : 'Esta plantilla no tiene contenido editor; deberás subir archivo.'
                        }}
                    </p>
                </div>

                <div v-if="isAdmin && form.document_mode === 'template'">
                    <label class="label">Area (opcional)</label>
                    <select v-model="form.area_id" class="input">
                        <option value="">Global</option>
                        <option v-for="area in areas" :key="area.id" :value="String(area.id)">
                            {{ area.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="label">Fecha inicio</label>
                    <input v-model="form.start_date" type="date" class="input" />
                </div>
                <div>
                    <label class="label">Fecha fin</label>
                    <input v-model="form.end_date" type="date" class="input" />
                </div>
                <div>
                    <label class="label">Vence el</label>
                    <input v-model="form.expires_at" type="date" class="input" />
                </div>
                <div>
                    <label class="label">
                        Archivo
                        {{
                            editingId
                                ? '(opcional)'
                                : form.document_mode === 'template' || form.template_id
                                  ? '(opcional si usas contenido de plantilla)'
                                  : '(requerido)'
                        }}
                    </label>
                    <input
                        type="file"
                        class="input"
                        @change="(e:any) => (form.file = e.target.files?.[0] ?? null)"
                    />
                </div>

                <div class="md:col-span-2">
                    <label class="label">Notas</label>
                    <textarea v-model="form.notes" rows="3" class="input"></textarea>
                </div>

                <div v-if="isAdmin && form.document_mode === 'template'" class="md:col-span-2 space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <label class="label mb-0">Editor de plantilla (tipo Word / Docs)</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Usa variables con formato <code v-pre>{{user.name}}</code>
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="variable in templateVariables"
                            :key="variable.key"
                            type="button"
                            class="btn-secondary"
                            @click="insertTemplateVariable(variable.key)"
                        >
                            <span v-text="'{{' + variable.key + '}}'"></span>
                        </button>
                    </div>

                    <textarea
                        v-model="form.template_content"
                        rows="12"
                        class="input font-mono text-sm"
                        placeholder="Escribe aquí el contrato plantilla. Ejemplo: Yo, {{user.name}}, identificado con {{user.identification}}..."
                    />
                </div>

                <div class="md:col-span-2 flex justify-end gap-2">
                    <button type="button" class="btn-secondary" @click="resetForm">Limpiar</button>
                    <button type="submit" class="btn-primary" :disabled="saving">
                        {{ saving ? 'Guardando...' : editingId ? 'Actualizar documento' : 'Guardar documento' }}
                    </button>
                </div>
            </form>

            <section class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Seccion: Contratos</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Vista resumida sin mostrar el archivo completo</p>
                </div>

                <div v-if="contracts.length === 0" class="py-4 text-center text-gray-500 dark:text-gray-400">
                    No hay contratos registrados.
                </div>
                <div v-else class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="doc in contracts"
                        :key="doc.id"
                        class="rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                    >
                        <div class="mb-2 flex items-start justify-between gap-3">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ doc.title }}</h3>
                            <span class="rounded-full px-2 py-1 text-xs" :class="statusClass(doc.status)">
                                {{ doc.status }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">Tipo: {{ modeLabel(doc.document_mode) }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Usuario: {{ doc.user?.name ?? 'No aplica' }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Firma: {{ signatureLabel(doc.signature_status) }}
                        </p>
                        <p v-if="doc.template" class="text-xs text-gray-500 dark:text-gray-400">
                            Base: {{ doc.template.title }}
                        </p>
                        <p v-if="doc.area" class="text-xs text-gray-500 dark:text-gray-400">
                            Area: {{ doc.area.name }}
                        </p>
                    </article>
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Seccion: Documentos por usuario</h2>
                    <div class="grid gap-2 md:grid-cols-5">
                        <input
                            v-model="filters.search"
                            class="input"
                            placeholder="Buscar titulo o usuario"
                        />
                        <select v-if="isAdmin" v-model="filters.user_id" class="input">
                            <option value="all">Todos los usuarios</option>
                            <option v-for="user in users" :key="user.id" :value="String(user.id)">
                                {{ user.name }}
                            </option>
                        </select>
                        <select v-model="filters.document_type" class="input">
                            <option value="all">Todos los tipos</option>
                            <option v-for="type in availableDocumentTypes" :key="type" :value="type">
                                {{ type }}
                            </option>
                        </select>
                        <select v-model="filters.document_mode" class="input">
                            <option value="all">Todas las modalidades</option>
                            <option value="template">General</option>
                            <option value="derived">Derivado</option>
                            <option value="user">Por usuario</option>
                        </select>
                        <select v-model="filters.signature_status" class="input">
                            <option value="all">Todas las firmas</option>
                            <option value="pending">Pendiente</option>
                            <option value="signed">Firmado</option>
                            <option value="rejected">Rechazado</option>
                            <option value="not_required">No requerida</option>
                        </select>
                    </div>
                </div>

                <div v-if="loading" class="py-6 text-center text-gray-500 dark:text-gray-400">Cargando...</div>
                <div v-else-if="filteredDocuments.length === 0" class="py-6 text-center text-gray-500 dark:text-gray-400">
                    No hay documentos para los filtros seleccionados.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-left text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                <th class="py-2">Documento</th>
                                <th class="py-2">Tipo</th>
                                <th class="py-2">Usuario</th>
                                <th class="py-2">Firma</th>
                                <th class="py-2">Fecha</th>
                                <th class="py-2 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="doc in filteredDocuments" :key="doc.id" class="border-b border-gray-100 align-top dark:border-gray-700">
                                <td class="py-3">
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ doc.title }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ doc.original_name }}</p>
                                    <p v-if="doc.template" class="text-xs text-gray-500 dark:text-gray-400">
                                        Derivado de: {{ doc.template.title }}
                                    </p>
                                    <p
                                        v-if="doc.rendered_content"
                                        class="mt-1 max-w-[420px] truncate text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        {{ doc.rendered_content }}
                                    </p>
                                </td>
                                <td class="py-3 text-gray-700 dark:text-gray-300">{{ modeLabel(doc.document_mode) }}</td>
                                <td class="py-3 text-gray-700 dark:text-gray-300">{{ doc.user?.name ?? 'Sin asignar' }}</td>
                                <td class="py-3">
                                    <span class="rounded-full px-2 py-1 text-xs" :class="statusClass(doc.status)">
                                        {{ signatureLabel(doc.signature_status) }}
                                    </span>
                                    <p v-if="doc.signed_at" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ formatDate(doc.signed_at, true) }}
                                    </p>
                                </td>
                                <td class="py-3 text-gray-700 dark:text-gray-300">{{ formatDate(doc.created_at) }}</td>
                                <td class="py-3 text-right">
                                    <a class="btn-secondary mr-2" :href="doc.download_url">Ver/Descargar</a>
                                    <button v-if="canEditDocument(doc)" class="btn-secondary mr-2" @click="fillForEdit(doc)">
                                        Asignar / Editar
                                    </button>
                                    <button
                                        v-if="canRequestSignature(doc)"
                                        class="btn-secondary mr-2"
                                        @click="requestSignature(doc)"
                                    >
                                        Solicitar firma
                                    </button>
                                    <button
                                        v-if="canSignDocument(doc)"
                                        class="btn-secondary mr-2"
                                        @click="openSignaturePanel(doc)"
                                    >
                                        Firmar
                                    </button>
                                    <button
                                        v-if="canSignDocument(doc)"
                                        class="btn-secondary mr-2"
                                        @click="rejectDocument(doc)"
                                    >
                                        Rechazar
                                    </button>
                                    <button
                                        class="btn-secondary mr-2"
                                        @click="loadAudits(doc.id)"
                                        :disabled="loadingAuditsFor === doc.id"
                                    >
                                        Historial
                                    </button>
                                    <button v-if="canDeleteDocument(doc)" class="btn-danger" @click="removeDocument(doc.id)">
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section
                v-if="signingDocument"
                class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Firma digital: {{ signingDocument.title }}
                    </h3>
                    <button class="btn-secondary" @click="closeSignaturePanel">Cerrar</button>
                </div>

                <div class="mb-3 flex gap-3">
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input v-model="signatureMethod" type="radio" class="input h-4 w-4" value="draw" />
                        Firma con mouse / tactil
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input v-model="signatureMethod" type="radio" class="input h-4 w-4" value="type" />
                        Escribir nombre
                    </label>
                </div>

                <div v-if="signatureMethod === 'draw'" class="space-y-2">
                    <canvas
                        ref="canvasRef"
                        class="h-44 w-full rounded-lg border border-dashed border-gray-300 bg-white touch-none"
                        @pointerdown="startDrawing"
                        @pointermove="draw"
                        @pointerup="endDrawing"
                        @pointerleave="endDrawing"
                    />
                    <button class="btn-secondary" @click="clearSignature">Limpiar firma</button>
                </div>

                <div v-else class="space-y-3">
                    <div>
                        <label class="label">Nombre completo</label>
                        <input v-model="signatureName" class="input" placeholder="Escribe tu nombre para firmar" />
                    </div>
                    <p class="rounded-md border border-gray-200 bg-gray-50 p-3 text-2xl italic text-gray-700">
                        {{ signatureName || 'Tu firma se vera aqui' }}
                    </p>
                </div>

                <div class="mt-4 flex justify-end">
                    <button class="btn-primary" :disabled="submittingSignature" @click="submitSignature">
                        {{ submittingSignature ? 'Firmando...' : 'Confirmar firma' }}
                    </button>
                </div>
            </section>

            <section
                v-if="selectedAuditDocumentId"
                class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Historial del documento #{{ selectedAuditDocumentId }}
                    </h3>
                    <button class="btn-secondary" @click="selectedAuditDocumentId = null">
                        Cerrar
                    </button>
                </div>

                <div v-if="loadingAuditsFor === selectedAuditDocumentId" class="py-4 text-gray-500 dark:text-gray-400">
                    Cargando historial...
                </div>
                <div v-else-if="selectedAudits.length === 0" class="py-4 text-gray-500 dark:text-gray-400">
                    Sin eventos registrados.
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="audit in selectedAudits"
                        :key="audit.id"
                        class="rounded-lg border border-gray-200 p-3 dark:border-gray-700"
                    >
                        <div class="flex items-center justify-between">
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ actionLabel(audit.action) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(audit.created_at, true) }}</p>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Usuario: {{ audit.user?.name ?? 'Sistema' }}
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
