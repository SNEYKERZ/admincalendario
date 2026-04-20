<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { useToast } from 'vue-toastification';
import { includesNormalized, normalizeForSearch } from '@/lib/search';

interface UserOption {
    id: number;
    name: string;
    email: string;
}

interface DocumentRow {
    id: number;
    title: string;
    document_type: string;
    status: string;
    user: { id: number; name: string; email: string } | null;
    uploader: { id: number; name: string } | null;
    start_date: string | null;
    end_date: string | null;
    expires_at: string | null;
    notes: string | null;
    original_name: string;
    mime_type: string;
    file_size: number;
    download_url: string;
    created_at: string;
}

interface DocumentAudit {
    id: number;
    action: string;
    old_values: Record<string, any> | null;
    new_values: Record<string, any> | null;
    user: { id: number; name: string; email: string } | null;
    created_at: string;
}

const toast = useToast();
const loading = ref(false);
const saving = ref(false);
const documents = ref<DocumentRow[]>([]);
const users = ref<UserOption[]>([]);
const filters = ref({
    search: '',
    status: 'all',
});
const editingId = ref<number | null>(null);
const loadingAuditsFor = ref<number | null>(null);
const selectedAuditDocumentId = ref<number | null>(null);
const audits = ref<Record<number, DocumentAudit[]>>({});

const metrics = ref({
    total: 0,
    active: 0,
    expiring_soon: 0,
    expired: 0,
});

const form = ref({
    title: '',
    document_type: 'contrato',
    status: 'activo',
    user_id: '',
    start_date: '',
    end_date: '',
    expires_at: '',
    notes: '',
    file: null as File | null,
});

const filteredDocuments = computed(() => {
    const search = normalizeForSearch(filters.value.search);
    return documents.value.filter((doc) => {
        const statusOk =
            filters.value.status === 'all' || doc.status === filters.value.status;
        const searchOk =
            search.length === 0 ||
            includesNormalized(doc.title, search) ||
            includesNormalized(doc.original_name, search) ||
            includesNormalized(doc.user?.name, search);

        return statusOk && searchOk;
    });
});

const selectedAudits = computed(() => {
    if (!selectedAuditDocumentId.value) return [];
    return audits.value[selectedAuditDocumentId.value] ?? [];
});

const formatDate = (value: string | null) =>
    value
        ? new Date(value).toLocaleDateString('es-CO', {
              year: 'numeric',
              month: 'short',
              day: 'numeric',
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
            return 'Creación';
        case 'updated':
            return 'Edición';
        case 'downloaded':
            return 'Descarga';
        case 'deleted':
            return 'Eliminación';
        default:
            return action;
    }
};

const resetForm = () => {
    form.value = {
        title: '',
        document_type: 'contrato',
        status: 'activo',
        user_id: '',
        start_date: '',
        end_date: '',
        expires_at: '',
        notes: '',
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
        metrics.value = response.data.metrics ?? metrics.value;
    } catch {
        toast.error('No se pudo cargar el módulo de documentos');
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
        status: doc.status,
        user_id: doc.user ? String(doc.user.id) : '',
        start_date: doc.start_date ?? '',
        end_date: doc.end_date ?? '',
        expires_at: doc.expires_at ?? '',
        notes: doc.notes ?? '',
        file: null,
    };
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const submit = async () => {
    if (!editingId.value && !form.value.file) {
        toast.error('Debes seleccionar un archivo');
        return;
    }

    saving.value = true;
    try {
        const payload = new FormData();
        payload.append('title', form.value.title);
        payload.append('document_type', form.value.document_type);
        payload.append('status', form.value.status);
        if (form.value.user_id) payload.append('user_id', form.value.user_id);
        if (form.value.start_date) payload.append('start_date', form.value.start_date);
        if (form.value.end_date) payload.append('end_date', form.value.end_date);
        if (form.value.expires_at) payload.append('expires_at', form.value.expires_at);
        if (form.value.notes) payload.append('notes', form.value.notes);
        if (form.value.file) payload.append('file', form.value.file);

        if (editingId.value) {
            await axios.post(`/documents/${editingId.value}`, payload, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            toast.success('Documento actualizado correctamente');
        } else {
            await axios.post('/documents', payload, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            toast.success('Documento creado correctamente');
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

onMounted(loadDocuments);
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Documentos y Contratos
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Gestión ligera con edición, alertas automáticas de vencimiento y trazabilidad.
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border bg-white p-4">
                    <p class="text-sm text-gray-500">Total</p>
                    <p class="text-2xl font-semibold">{{ metrics.total }}</p>
                </div>
                <div class="rounded-lg border bg-white p-4">
                    <p class="text-sm text-gray-500">Activos</p>
                    <p class="text-2xl font-semibold text-emerald-600">{{ metrics.active }}</p>
                </div>
                <div class="rounded-lg border bg-white p-4">
                    <p class="text-sm text-gray-500">Vencen en 30 días</p>
                    <p class="text-2xl font-semibold text-amber-600">{{ metrics.expiring_soon }}</p>
                </div>
                <div class="rounded-lg border bg-white p-4">
                    <p class="text-sm text-gray-500">Vencidos</p>
                    <p class="text-2xl font-semibold text-rose-600">{{ metrics.expired }}</p>
                </div>
            </div>

            <form
                class="grid gap-4 rounded-xl border border-gray-200 bg-white p-5 md:grid-cols-2"
                @submit.prevent="submit"
            >
                <div class="md:col-span-2 flex items-center justify-between">
                    <h2 class="text-lg font-semibold">
                        {{ editingId ? 'Editar documento' : 'Nuevo documento' }}
                    </h2>
                    <button
                        v-if="editingId"
                        type="button"
                        class="btn-secondary"
                        @click="resetForm"
                    >
                        Cancelar edición
                    </button>
                </div>

                <div>
                    <label class="label">Título</label>
                    <input v-model="form.title" required class="input" />
                </div>
                <div>
                    <label class="label">Tipo</label>
                    <select v-model="form.document_type" class="input">
                        <option value="contrato">Contrato</option>
                        <option value="certificacion">Certificación</option>
                        <option value="politica">Política</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div>
                    <label class="label">Estado</label>
                    <select v-model="form.status" class="input">
                        <option value="activo">Activo</option>
                        <option value="borrador">Borrador</option>
                        <option value="vencido">Vencido</option>
                    </select>
                </div>
                <div>
                    <label class="label">Colaborador</label>
                    <select v-model="form.user_id" class="input">
                        <option value="">Sin asignar</option>
                        <option v-for="user in users" :key="user.id" :value="String(user.id)">
                            {{ user.name }}
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
                        Archivo {{ editingId ? '(opcional)' : '(requerido)' }}
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
                <div class="md:col-span-2 flex justify-end gap-2">
                    <button type="button" class="btn-secondary" @click="resetForm">Limpiar</button>
                    <button type="submit" class="btn-primary" :disabled="saving">
                        {{ saving ? 'Guardando...' : editingId ? 'Actualizar documento' : 'Guardar documento' }}
                    </button>
                </div>
            </form>

            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <h2 class="text-lg font-semibold">Repositorio documental</h2>
                    <div class="flex gap-2">
                        <input
                            v-model="filters.search"
                            class="input"
                            placeholder="Buscar por título, archivo o usuario"
                        />
                        <select v-model="filters.status" class="input">
                            <option value="all">Todos</option>
                            <option value="activo">Activo</option>
                            <option value="borrador">Borrador</option>
                            <option value="vencido">Vencido</option>
                        </select>
                    </div>
                </div>

                <div v-if="loading" class="py-6 text-center text-gray-500">Cargando...</div>
                <div v-else-if="filteredDocuments.length === 0" class="py-6 text-center text-gray-500">
                    No hay documentos registrados
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-gray-500">
                                <th class="py-2">Documento</th>
                                <th class="py-2">Colaborador</th>
                                <th class="py-2">Estado</th>
                                <th class="py-2">Vencimiento</th>
                                <th class="py-2">Tamaño</th>
                                <th class="py-2 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="doc in filteredDocuments" :key="doc.id" class="border-b align-top">
                                <td class="py-3">
                                    <p class="font-medium text-gray-900">{{ doc.title }}</p>
                                    <p class="text-xs text-gray-500">{{ doc.original_name }}</p>
                                </td>
                                <td class="py-3">{{ doc.user?.name ?? 'Sin asignar' }}</td>
                                <td class="py-3">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs"
                                        :class="{
                                            'bg-emerald-100 text-emerald-700': doc.status === 'activo',
                                            'bg-amber-100 text-amber-700': doc.status === 'borrador',
                                            'bg-rose-100 text-rose-700': doc.status === 'vencido',
                                        }"
                                    >
                                        {{ doc.status }}
                                    </span>
                                </td>
                                <td class="py-3">{{ formatDate(doc.expires_at) }}</td>
                                <td class="py-3">{{ formatSize(doc.file_size) }}</td>
                                <td class="py-3 text-right">
                                    <a class="btn-secondary mr-2" :href="doc.download_url">Descargar</a>
                                    <button class="btn-secondary mr-2" @click="fillForEdit(doc)">Editar</button>
                                    <button
                                        class="btn-secondary mr-2"
                                        @click="loadAudits(doc.id)"
                                        :disabled="loadingAuditsFor === doc.id"
                                    >
                                        Historial
                                    </button>
                                    <button class="btn-danger" @click="removeDocument(doc.id)">
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div
                v-if="selectedAuditDocumentId"
                class="rounded-xl border border-gray-200 bg-white p-5"
            >
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-lg font-semibold">
                        Historial del documento #{{ selectedAuditDocumentId }}
                    </h3>
                    <button class="btn-secondary" @click="selectedAuditDocumentId = null">
                        Cerrar
                    </button>
                </div>

                <div v-if="loadingAuditsFor === selectedAuditDocumentId" class="py-4 text-gray-500">
                    Cargando historial...
                </div>
                <div v-else-if="selectedAudits.length === 0" class="py-4 text-gray-500">
                    Sin eventos registrados.
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="audit in selectedAudits"
                        :key="audit.id"
                        class="rounded-lg border border-gray-200 p-3"
                    >
                        <div class="flex items-center justify-between">
                            <p class="font-medium text-gray-900">{{ actionLabel(audit.action) }}</p>
                            <p class="text-xs text-gray-500">{{ formatDate(audit.created_at) }}</p>
                        </div>
                        <p class="text-xs text-gray-500">
                            Usuario: {{ audit.user?.name ?? 'Sistema' }}
                        </p>
                        <p v-if="audit.new_values" class="mt-1 text-xs text-gray-600">
                            Cambios: {{ Object.keys(audit.new_values).join(', ') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
