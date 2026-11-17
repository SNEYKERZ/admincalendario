<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Check, ClipboardCheck, Search, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import EmptyState from '@/components/app/EmptyState.vue';
import FormField from '@/components/app/FormField.vue';
import PageContainer from '@/components/app/PageContainer.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import SectionCard from '@/components/app/SectionCard.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import TablePagination from '@/components/app/TablePagination.vue';
import RequestTimeline from '@/components/requests/RequestTimeline.vue';
import {
    formatRequestDate,
    getTypeLabel,
    REQUEST_STATUSES,
    REQUEST_TYPES
    
} from '@/components/requests/utils';
import type {RequestApproval} from '@/components/requests/utils';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useNotification } from '@/composables/useNotification';
import AppLayout from '@/layouts/AppLayout.vue';
import { normalizeForSearch } from '@/lib/search';
import { cn } from '@/lib/utils';

interface EmployeeRequest {
    id: number;
    request_type: string;
    status: string;
    title: string;
    description: string | null;
    created_at: string;
    user_name: string;
    user_email: string;
    approvals?: RequestApproval[];
}

interface PaginatedRequests {
    data: EmployeeRequest[];
    current_page?: number;
    last_page?: number;
    total?: number;
}

const props = defineProps<{
    requests: PaginatedRequests;
    status?: string;
    type?: string;
    requestTypes?: Record<string, string>;
}>();

const notify = useNotification();

const types = computed<Record<string, string>>(() =>
    props.requestTypes && Object.keys(props.requestTypes).length
        ? props.requestTypes
        : REQUEST_TYPES,
);

const selectedStatus = ref(props.status ?? '');
const selectedType = ref(props.type ?? '');
const searchQuery = ref('');
const loadingList = ref(false);

const rows = computed(() =>
    Array.isArray(props.requests?.data) ? props.requests.data : [],
);

const filteredRequests = computed(() => {
    const term = normalizeForSearch(searchQuery.value);
    if (!term) return rows.value;
    return rows.value.filter((r) =>
        normalizeForSearch(`${r.user_name} ${r.user_email} ${r.title}`).includes(
            term,
        ),
    );
});

const hasActiveFilters = computed(
    () => !!selectedStatus.value || !!selectedType.value || !!searchQuery.value,
);

const visit = (extra: Record<string, string | number> = {}) => {
    const params: Record<string, string | number> = { ...extra };
    if (selectedStatus.value) params.status = selectedStatus.value;
    if (selectedType.value) params.type = selectedType.value;
    router.get('/admin/solicitudes', params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => (loadingList.value = true),
        onFinish: () => (loadingList.value = false),
    });
};

const setStatus = (value: string) => {
    selectedStatus.value = value;
    visit();
};

const resetFilters = () => {
    selectedStatus.value = '';
    selectedType.value = '';
    searchQuery.value = '';
    visit();
};

// --- Gestión
type Mode = 'view' | 'approve' | 'reject';
const selectedRequest = ref<EmployeeRequest | null>(null);
const mode = ref<Mode>('view');
const approvalComment = ref('');
const rejectionReason = ref('');
const rejectError = ref('');
const submitting = ref(false);

const dialogOpen = computed({
    get: () => selectedRequest.value !== null,
    set: (v: boolean) => {
        if (!v && !submitting.value) selectedRequest.value = null;
    },
});

const openManage = (request: EmployeeRequest) => {
    selectedRequest.value = request;
    mode.value = 'view';
    approvalComment.value = '';
    rejectionReason.value = '';
    rejectError.value = '';
};

const approveRequest = () => {
    if (!selectedRequest.value) return;
    router.post(
        `/admin/solicitudes/${selectedRequest.value.id}/aprobar`,
        { comment: approvalComment.value.trim() || null },
        {
            preserveScroll: true,
            preserveState: true,
            onStart: () => (submitting.value = true),
            onFinish: () => (submitting.value = false),
            onSuccess: () => {
                selectedRequest.value = null;
                notify.success('Solicitud aprobada.');
            },
            onError: () => notify.error('No se pudo aprobar la solicitud.'),
        },
    );
};

const rejectRequest = () => {
    if (!selectedRequest.value) return;
    if (!rejectionReason.value.trim()) {
        rejectError.value = 'Indica el motivo del rechazo.';
        return;
    }
    router.post(
        `/admin/solicitudes/${selectedRequest.value.id}/rechazar`,
        { reason: rejectionReason.value.trim() },
        {
            preserveScroll: true,
            preserveState: true,
            onStart: () => (submitting.value = true),
            onFinish: () => (submitting.value = false),
            onSuccess: () => {
                selectedRequest.value = null;
                notify.success('Solicitud rechazada.');
            },
            onError: (errors) => {
                rejectError.value =
                    (errors as Record<string, string>).reason ??
                    'No se pudo rechazar la solicitud.';
            },
        },
    );
};
</script>

<template>
    <Head title="Solicitudes del equipo" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Solicitudes del equipo', href: '/admin/solicitudes' },
        ]"
    >
        <PageContainer>
            <PageHeader
                title="Solicitudes del equipo"
                description="Revisa y resuelve las solicitudes que los colaboradores envían a Recursos Humanos."
                :icon="ClipboardCheck"
            />

            <SectionCard flush>
                <template #header>
                    <div class="flex w-full flex-col gap-3">
                        <div
                            role="tablist"
                            aria-label="Filtrar por estado"
                            class="inline-flex w-full gap-1 self-start overflow-x-auto rounded-lg bg-muted p-1 sm:w-auto"
                        >
                            <button
                                v-for="s in REQUEST_STATUSES"
                                :key="s.value"
                                type="button"
                                role="tab"
                                :aria-selected="selectedStatus === s.value"
                                :class="
                                    cn(
                                        'inline-flex h-7 items-center rounded-md px-3 text-[13px] font-medium whitespace-nowrap transition-colors',
                                        selectedStatus === s.value
                                            ? 'bg-card text-foreground shadow-xs'
                                            : 'text-muted-foreground hover:text-foreground',
                                    )
                                "
                                @click="setStatus(s.value)"
                            >
                                {{ s.label }}
                            </button>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_220px_auto]">
                            <div class="relative">
                                <Search
                                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                <Input
                                    v-model="searchQuery"
                                    type="search"
                                    class="pl-9"
                                    placeholder="Buscar por empleado, correo o asunto"
                                    aria-label="Buscar solicitudes"
                                />
                            </div>
                            <div>
                                <label class="sr-only" for="ar-type">Tipo</label>
                                <select
                                    id="ar-type"
                                    v-model="selectedType"
                                    class="input"
                                    @change="visit()"
                                >
                                    <option value="">Todos los tipos</option>
                                    <option
                                        v-for="(label, key) in types"
                                        :key="key"
                                        :value="key"
                                    >
                                        {{ label }}
                                    </option>
                                </select>
                            </div>
                            <Button
                                variant="ghost"
                                :disabled="!hasActiveFilters"
                                @click="resetFilters"
                            >
                                Limpiar filtros
                            </Button>
                        </div>
                    </div>
                </template>

                <div
                    class="transition-opacity"
                    :class="loadingList ? 'pointer-events-none opacity-60' : ''"
                    :aria-busy="loadingList"
                >
                    <EmptyState
                        v-if="!filteredRequests.length"
                        :icon="ClipboardCheck"
                        :title="
                            hasActiveFilters
                                ? 'Ninguna solicitud coincide con los filtros'
                                : 'No hay solicitudes'
                        "
                        :description="
                            hasActiveFilters
                                ? 'Prueba a cambiar el estado, el tipo o la búsqueda.'
                                : 'Cuando un colaborador envíe una solicitud aparecerá aquí.'
                        "
                    >
                        <Button
                            v-if="hasActiveFilters"
                            variant="outline"
                            size="sm"
                            @click="resetFilters"
                        >
                            Limpiar filtros
                        </Button>
                    </EmptyState>

                    <template v-else>
                        <div class="hidden overflow-x-auto md:block">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Empleado</th>
                                        <th>Asunto</th>
                                        <th>Tipo</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                        <th class="text-right">
                                            <span class="sr-only">Acciones</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="request in filteredRequests"
                                        :key="request.id"
                                    >
                                        <td>
                                            <p class="font-medium whitespace-nowrap">
                                                {{ request.user_name }}
                                            </p>
                                            <p class="text-xs text-muted-foreground">
                                                {{ request.user_email }}
                                            </p>
                                        </td>
                                        <td class="max-w-xs">
                                            <p class="truncate" :title="request.title">
                                                {{ request.title }}
                                            </p>
                                        </td>
                                        <td class="whitespace-nowrap text-muted-foreground">
                                            {{ getTypeLabel(request.request_type) }}
                                        </td>
                                        <td>
                                            <StatusBadge :status="request.status" />
                                        </td>
                                        <td class="whitespace-nowrap text-muted-foreground">
                                            {{ formatRequestDate(request.created_at) }}
                                        </td>
                                        <td class="text-right">
                                            <Button
                                                size="sm"
                                                :variant="
                                                    request.status === 'pendiente'
                                                        ? 'default'
                                                        : 'outline'
                                                "
                                                @click="openManage(request)"
                                            >
                                                {{
                                                    request.status === 'pendiente'
                                                        ? 'Revisar'
                                                        : 'Ver'
                                                }}
                                            </Button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <ul class="divide-y md:hidden">
                            <li v-for="request in filteredRequests" :key="request.id">
                                <button
                                    type="button"
                                    class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-muted/40"
                                    @click="openManage(request)"
                                >
                                    <div class="min-w-0 flex-1 space-y-1">
                                        <p class="truncate text-sm font-medium">
                                            {{ request.title }}
                                        </p>
                                        <p class="truncate text-xs text-muted-foreground">
                                            {{ request.user_name }} ·
                                            {{ getTypeLabel(request.request_type) }} ·
                                            {{ formatRequestDate(request.created_at) }}
                                        </p>
                                    </div>
                                    <StatusBadge :status="request.status" />
                                </button>
                            </li>
                        </ul>
                    </template>
                </div>

                <template v-if="(requests.last_page ?? 1) > 1" #footer>
                    <TablePagination
                        class="w-full"
                        :page="requests.current_page ?? 1"
                        :last-page="requests.last_page ?? 1"
                        :total="requests.total"
                        label="solicitudes"
                        @change="(p: number) => visit({ page: p })"
                    />
                </template>
            </SectionCard>
        </PageContainer>

        <Dialog v-model:open="dialogOpen">
            <DialogContent v-if="selectedRequest" class="sm:max-w-xl">
                <DialogHeader>
                    <div class="flex flex-wrap items-center gap-2 pr-6">
                        <DialogTitle>{{ selectedRequest.title }}</DialogTitle>
                        <StatusBadge :status="selectedRequest.status" />
                    </div>
                    <DialogDescription>
                        {{ getTypeLabel(selectedRequest.request_type) }} · enviada el
                        {{ formatRequestDate(selectedRequest.created_at, true) }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 text-sm">
                    <div class="rounded-lg border bg-muted/40 px-3 py-2.5">
                        <p class="font-medium">{{ selectedRequest.user_name }}</p>
                        <p class="text-muted-foreground">
                            {{ selectedRequest.user_email }}
                        </p>
                    </div>

                    <div>
                        <p class="text-[13px] text-muted-foreground">Descripción</p>
                        <p
                            class="mt-1 whitespace-pre-wrap"
                            :class="
                                selectedRequest.description
                                    ? ''
                                    : 'text-muted-foreground'
                            "
                        >
                            {{ selectedRequest.description || 'Sin descripción.' }}
                        </p>
                    </div>

                    <div v-if="selectedRequest.approvals?.length">
                        <p class="mb-2 text-[13px] text-muted-foreground">
                            Historial de decisiones
                        </p>
                        <RequestTimeline :approvals="selectedRequest.approvals" />
                    </div>

                    <form
                        v-if="mode === 'approve'"
                        id="ar-approve-form"
                        @submit.prevent="approveRequest"
                    >
                        <FormField
                            label="Comentario para el colaborador"
                            for="ar-approve-comment"
                            hint="Opcional. Ej.: «El certificado está disponible en Documentos»."
                        >
                            <Textarea
                                id="ar-approve-comment"
                                v-model="approvalComment"
                                rows="3"
                            />
                        </FormField>
                    </form>

                    <form
                        v-if="mode === 'reject'"
                        id="ar-reject-form"
                        @submit.prevent="rejectRequest"
                    >
                        <FormField
                            label="Motivo del rechazo"
                            for="ar-reject-reason"
                            required
                            hint="El colaborador verá este motivo."
                            :error="rejectError"
                        >
                            <Textarea
                                id="ar-reject-reason"
                                v-model="rejectionReason"
                                rows="3"
                                maxlength="500"
                                required
                            />
                        </FormField>
                    </form>
                </div>

                <DialogFooter v-if="selectedRequest.status === 'pendiente'">
                    <template v-if="mode === 'view'">
                        <Button
                            variant="outline"
                            class="text-destructive hover:bg-destructive-soft hover:text-destructive"
                            @click="mode = 'reject'"
                        >
                            <X />
                            Rechazar
                        </Button>
                        <Button @click="mode = 'approve'">
                            <Check />
                            Aprobar
                        </Button>
                    </template>
                    <template v-else>
                        <Button
                            variant="outline"
                            :disabled="submitting"
                            @click="mode = 'view'"
                        >
                            Atrás
                        </Button>
                        <Button
                            v-if="mode === 'approve'"
                            type="submit"
                            form="ar-approve-form"
                            :disabled="submitting"
                        >
                            <Spinner v-if="submitting" />
                            {{ submitting ? 'Aprobando…' : 'Confirmar aprobación' }}
                        </Button>
                        <Button
                            v-else
                            type="submit"
                            form="ar-reject-form"
                            variant="destructive"
                            :disabled="submitting"
                        >
                            <Spinner v-if="submitting" />
                            {{ submitting ? 'Rechazando…' : 'Confirmar rechazo' }}
                        </Button>
                    </template>
                </DialogFooter>
                <DialogFooter v-else>
                    <Button variant="outline" @click="selectedRequest = null">
                        Cerrar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
