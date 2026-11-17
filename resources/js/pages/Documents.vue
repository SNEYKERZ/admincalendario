<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import {
    CheckCircle2,
    Clock,
    FileStack,
    Plus,
    XCircle,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import PageContainer from '@/components/app/PageContainer.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import StatCard from '@/components/app/StatCard.vue';
import AuditSheet from '@/components/documents/AuditSheet.vue';
import ContractsSummary from '@/components/documents/ContractsSummary.vue';
import DocumentFormSheet from '@/components/documents/DocumentFormSheet.vue';
import DocumentsTable from '@/components/documents/DocumentsTable.vue';
import RejectDialog from '@/components/documents/RejectDialog.vue';
import SignatureDialog from '@/components/documents/SignatureDialog.vue';
import type {
    AreaOption,
    DocumentAudit,
    DocumentFilters,
    DocumentPermissions,
    DocumentRow,
    TemplateOption,
    TemplateVariable,
    UserOption,
} from '@/components/documents/types';
import { Button } from '@/components/ui/button';
import { useConfirm } from '@/composables/useConfirm';
import { useNotification } from '@/composables/useNotification';
import AppLayout from '@/layouts/AppLayout.vue';
import { includesNormalized, normalizeForSearch } from '@/lib/search';

const notify = useNotification();
const confirm = useConfirm();

const loading = ref(true);
const busyId = ref<number | null>(null);
const documents = ref<DocumentRow[]>([]);
const users = ref<UserOption[]>([]);
const areas = ref<AreaOption[]>([]);
const templates = ref<TemplateOption[]>([]);
const templateVariables = ref<TemplateVariable[]>([]);
const currentUserId = ref<number | null>(null);
const permissions = ref<DocumentPermissions>({
    is_admin: false,
    is_superadmin: false,
    can_manage_templates: false,
    can_sign: true,
    max_personal_uploads: 5,
});
const metrics = ref({
    total: 0,
    active: 0,
    pending_signature: 0,
    signed: 0,
    rejected: 0,
    expiring_soon: 0,
    expired: 0,
});

const filters = ref<DocumentFilters>({
    search: '',
    user_id: 'all',
    document_type: 'all',
    document_mode: 'all',
    signature_status: 'all',
});

// Paneles
const formOpen = ref(false);
const editingDocument = ref<DocumentRow | null>(null);
const signingDocument = ref<DocumentRow | null>(null);
const rejectingDocument = ref<DocumentRow | null>(null);
const auditDocument = ref<DocumentRow | null>(null);
const audits = ref<Record<number, DocumentAudit[]>>({});
const loadingAudits = ref(false);

const isAdmin = computed(() => permissions.value.is_admin);
const canManageTemplates = computed(() => permissions.value.can_manage_templates);

const personalUploadsCount = computed(
    () =>
        documents.value.filter(
            (doc) => doc.is_personal_upload && doc.uploader?.id === currentUserId.value,
        ).length,
);
const uploadLimitReached = computed(
    () =>
        !isAdmin.value &&
        personalUploadsCount.value >= permissions.value.max_personal_uploads,
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

const availableDocumentTypes = computed(() =>
    Array.from(new Set(documents.value.map((doc) => doc.document_type))).sort(),
);

const filteredDocuments = computed(() => {
    const search = normalizeForSearch(filters.value.search);
    const f = filters.value;
    return documents.value.filter((doc) => {
        const searchOk =
            search.length === 0 ||
            includesNormalized(doc.title, search) ||
            includesNormalized(doc.original_name, search) ||
            includesNormalized(doc.user?.name, search);
        const userOk = f.user_id === 'all' || String(doc.user?.id ?? '') === f.user_id;
        const typeOk = f.document_type === 'all' || doc.document_type === f.document_type;
        const modeOk = f.document_mode === 'all' || doc.document_mode === f.document_mode;
        const signatureOk =
            f.signature_status === 'all' || doc.signature_status === f.signature_status;
        return searchOk && userOk && typeOk && modeOk && signatureOk;
    });
});

const currentAudits = computed(() =>
    auditDocument.value ? (audits.value[auditDocument.value.id] ?? []) : [],
);

const loadDocuments = async () => {
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
        notify.error('No se pudo cargar el módulo de documentos');
    } finally {
        loading.value = false;
    }
};

// Permisos por fila (misma lógica que antes)
const canEditDocument = (doc: DocumentRow) =>
    isAdmin.value && (doc.document_mode !== 'template' || canManageTemplates.value);
const canDeleteDocument = canEditDocument;
const canRequestSignature = (doc: DocumentRow) =>
    isAdmin.value &&
    doc.document_mode !== 'template' &&
    !!doc.user &&
    doc.signature_status !== 'signed';
const canSignDocument = (doc: DocumentRow) => {
    if (doc.signature_status !== 'pending') return false;
    if (isAdmin.value) return doc.document_mode !== 'template';
    return doc.user?.id === currentUserId.value;
};

const openCreate = () => {
    if (uploadLimitReached.value) {
        notify.warning(
            `Alcanzaste el límite de ${permissions.value.max_personal_uploads} documentos personales.`,
        );
        return;
    }
    editingDocument.value = null;
    formOpen.value = true;
};

const openEdit = (doc: DocumentRow) => {
    editingDocument.value = doc;
    formOpen.value = true;
};

const requestSignature = async (doc: DocumentRow) => {
    busyId.value = doc.id;
    try {
        await axios.post(`/documents/${doc.id}/request-signature`);
        notify.success(`Firma solicitada a ${doc.user?.name ?? 'el colaborador'}`);
        await loadDocuments();
    } catch (e: any) {
        notify.error(e?.response?.data?.message ?? 'No fue posible solicitar la firma');
    } finally {
        busyId.value = null;
    }
};

const removeDocument = async (doc: DocumentRow) => {
    const ok = await confirm({
        title: 'Eliminar documento',
        description: `«${doc.title}» se eliminará de forma permanente junto con su archivo.`,
        confirmText: 'Eliminar',
        variant: 'destructive',
    });
    if (!ok) return;

    busyId.value = doc.id;
    try {
        await axios.delete(`/documents/${doc.id}`);
        notify.success('Documento eliminado');
        if (auditDocument.value?.id === doc.id) auditDocument.value = null;
        await loadDocuments();
    } catch (e: any) {
        notify.error(e?.response?.data?.message ?? 'No fue posible eliminar el documento');
    } finally {
        busyId.value = null;
    }
};

const openHistory = async (doc: DocumentRow) => {
    auditDocument.value = doc;
    loadingAudits.value = true;
    try {
        const response = await axios.get(`/documents/${doc.id}/audits`);
        audits.value[doc.id] = response.data.audits ?? [];
    } catch {
        notify.error('No fue posible cargar el historial');
    } finally {
        loadingAudits.value = false;
    }
};

onMounted(loadDocuments);
</script>

<template>
    <Head title="Documentos" />
    <AppLayout>
        <PageContainer>
            <PageHeader
                title="Contratos y documentos"
                :description="
                    isAdmin
                        ? 'Plantillas, documentos por colaborador y firma digital en un solo lugar.'
                        : 'Consulta, descarga y firma tus documentos laborales.'
                "
            >
                <template #actions>
                    <span
                        v-if="!isAdmin && !loading"
                        class="text-xs text-muted-foreground tabular-nums"
                    >
                        Documentos personales: {{ personalUploadsCount }}/{{
                            permissions.max_personal_uploads
                        }}
                    </span>
                    <Button :disabled="loading || uploadLimitReached" @click="openCreate">
                        <Plus />
                        {{ isAdmin ? 'Nuevo documento' : 'Subir documento' }}
                    </Button>
                </template>
            </PageHeader>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <StatCard
                    label="Total documentos"
                    :value="metrics.total"
                    :icon="FileStack"
                    :loading="loading"
                />
                <StatCard
                    label="Pendientes de firma"
                    :value="metrics.pending_signature"
                    :icon="Clock"
                    tone="warning"
                    :loading="loading"
                />
                <StatCard
                    label="Firmados"
                    :value="metrics.signed"
                    :icon="CheckCircle2"
                    tone="success"
                    :loading="loading"
                />
                <StatCard
                    label="Rechazados"
                    :value="metrics.rejected"
                    :icon="XCircle"
                    tone="destructive"
                    :loading="loading"
                />
            </div>

            <DocumentsTable
                v-model:filters="filters"
                :documents="filteredDocuments"
                :total-count="documents.length"
                :loading="loading"
                :users="users"
                :document-types="availableDocumentTypes"
                :is-admin="isAdmin"
                :busy-id="busyId"
                :can-edit="canEditDocument"
                :can-delete="canDeleteDocument"
                :can-request-signature="canRequestSignature"
                :can-sign="canSignDocument"
                @edit="openEdit"
                @request-signature="requestSignature"
                @sign="(doc) => (signingDocument = doc)"
                @reject="(doc) => (rejectingDocument = doc)"
                @history="openHistory"
                @remove="removeDocument"
            />

            <ContractsSummary v-if="!loading" :contracts="contracts" />
        </PageContainer>

        <DocumentFormSheet
            v-model:open="formOpen"
            :document="editingDocument"
            :users="users"
            :areas="areas"
            :templates="templates"
            :template-variables="templateVariables"
            :is-admin="isAdmin"
            :can-manage-templates="canManageTemplates"
            @saved="loadDocuments"
        />
        <SignatureDialog
            :document="signingDocument"
            @close="signingDocument = null"
            @signed="loadDocuments"
        />
        <RejectDialog
            :document="rejectingDocument"
            @close="rejectingDocument = null"
            @rejected="loadDocuments"
        />
        <AuditSheet
            :document="auditDocument"
            :audits="currentAudits"
            :loading="loadingAudits"
            @close="auditDocument = null"
        />
    </AppLayout>
</template>
