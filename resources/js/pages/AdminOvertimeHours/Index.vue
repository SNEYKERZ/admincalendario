<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Check, Download, Pencil, Timer, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import EmptyState from '@/components/app/EmptyState.vue';
import FormField from '@/components/app/FormField.vue';
import PageContainer from '@/components/app/PageContainer.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import SectionCard from '@/components/app/SectionCard.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import TablePagination from '@/components/app/TablePagination.vue';
import OvertimeEditDialog from '@/components/overtime/OvertimeEditDialog.vue';
import {
    formatDate,
    formatHours,
    formatTimeRange,
    MONTHS,
    YEARS
    
    
} from '@/components/overtime/utils';
import type {OvertimeFormValues, OvertimeRecord} from '@/components/overtime/utils';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useConfirm } from '@/composables/useConfirm';
import { useNotification } from '@/composables/useNotification';
import AppLayout from '@/layouts/AppLayout.vue';

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
};

type Filters = {
    area_id: string | number;
    user_id: string | number;
    month: string;
    year: string | number;
};

const props = defineProps<{
    overtimes: Paginated<OvertimeRecord>;
    areas: Array<{ id: number; name: string }>;
    employees: Array<{ id: number; name: string }>;
    filters: Filters;
}>();

const notify = useNotification();
const confirm = useConfirm();

const filters = ref<Filters>({
    area_id: props.filters?.area_id ?? '',
    user_id: props.filters?.user_id ?? '',
    month: props.filters?.month ?? '',
    year: String(props.filters?.year ?? new Date().getFullYear()),
});

const rows = computed<OvertimeRecord[]>(() =>
    Array.isArray(props.overtimes?.data) ? props.overtimes.data : [],
);

const hasActiveFilters = computed(
    () =>
        !!filters.value.area_id ||
        !!filters.value.user_id ||
        !!filters.value.month,
);

const queryParams = (extra: Record<string, string | number> = {}) => {
    const params: Record<string, string> = {};
    Object.entries({ ...filters.value, ...extra }).forEach(([k, v]) => {
        if (v !== '' && v !== null && v !== undefined) params[k] = String(v);
    });
    return params;
};

const exportUrl = computed(
    () =>
        `/admin/overtime-hours/export?${new URLSearchParams(queryParams()).toString()}`,
);

const loadingList = ref(false);

const visit = (extra: Record<string, string | number> = {}) => {
    router.get('/admin/overtime-hours', queryParams(extra), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => (loadingList.value = true),
        onFinish: () => (loadingList.value = false),
    });
};

const applyFilters = () => visit();
const goToPage = (p: number) => visit({ page: p });
const resetFilters = () => {
    filters.value = {
        area_id: '',
        user_id: '',
        month: '',
        year: String(new Date().getFullYear()),
    };
    visit();
};

// --- Edición
const editingOT = ref<OvertimeRecord | null>(null);
const editErrors = ref<Record<string, string>>({});
const savingEdit = ref(false);

const openEdit = (ot: OvertimeRecord) => {
    editErrors.value = {};
    editingOT.value = ot;
};

const saveEdit = (values: OvertimeFormValues) => {
    if (!editingOT.value) return;
    router.put(
        `/admin/overtime-hours/${editingOT.value.id}`,
        {
            date: values.date,
            start_time: values.start_time,
            end_time: values.end_time,
            project: values.project || null,
            client: values.client || null,
            reason: values.reason,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onStart: () => (savingEdit.value = true),
            onFinish: () => (savingEdit.value = false),
            onSuccess: () => {
                editingOT.value = null;
                notify.success('Registro actualizado.');
            },
            onError: (errors) => {
                editErrors.value = errors as Record<string, string>;
            },
        },
    );
};

// --- Aprobación
const processingId = ref<number | null>(null);

const approve = async (ot: OvertimeRecord) => {
    const ok = await confirm({
        title: 'Aprobar horas extra',
        description: `${ot.user?.name ?? 'Empleado'} · ${formatDate(ot.date)} · ${formatHours(ot.hours)}`,
        confirmText: 'Aprobar',
    });
    if (!ok) return;
    router.post(
        `/admin/overtime-hours/${ot.id}/approve`,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onStart: () => (processingId.value = ot.id),
            onFinish: () => (processingId.value = null),
            onSuccess: () => notify.success('Registro aprobado.'),
            onError: () => notify.error('No se pudo aprobar el registro.'),
        },
    );
};

// --- Rechazo con motivo
const rejectingOT = ref<OvertimeRecord | null>(null);
const rejectReason = ref('');
const rejectError = ref('');
const rejecting = ref(false);

const rejectOpen = computed({
    get: () => rejectingOT.value !== null,
    set: (v: boolean) => {
        if (!v) rejectingOT.value = null;
    },
});

const openReject = (ot: OvertimeRecord) => {
    rejectReason.value = '';
    rejectError.value = '';
    rejectingOT.value = ot;
};

const reject = () => {
    if (!rejectingOT.value) return;
    if (!rejectReason.value.trim()) {
        rejectError.value = 'Indica el motivo del rechazo.';
        return;
    }
    router.post(
        `/admin/overtime-hours/${rejectingOT.value.id}/reject`,
        { reason: rejectReason.value.trim() },
        {
            preserveScroll: true,
            preserveState: true,
            onStart: () => (rejecting.value = true),
            onFinish: () => (rejecting.value = false),
            onSuccess: () => {
                rejectingOT.value = null;
                notify.success('Registro rechazado.');
            },
            onError: (errors) => {
                rejectError.value =
                    (errors as Record<string, string>).reason ??
                    'No se pudo rechazar el registro.';
            },
        },
    );
};
</script>

<template>
    <Head title="Horas extra del equipo" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Horas extra del equipo', href: '/admin/overtime-hours' },
        ]"
    >
        <PageContainer>
            <PageHeader
                title="Horas extra del equipo"
                description="Revisa, edita y aprueba las horas extra registradas por los colaboradores."
                :icon="Timer"
            >
                <template #actions>
                    <Button as-child variant="outline">
                        <a :href="exportUrl">
                            <Download />
                            Exportar a Excel
                        </a>
                    </Button>
                </template>
            </PageHeader>

            <SectionCard flush>
                <template #header>
                    <div
                        class="grid w-full gap-2 sm:grid-cols-2 lg:grid-cols-[repeat(4,minmax(0,1fr))_auto]"
                    >
                        <div>
                            <label class="sr-only" for="aot-area">Área</label>
                            <select
                                id="aot-area"
                                v-model="filters.area_id"
                                class="input"
                                @change="applyFilters"
                            >
                                <option value="">Todas las áreas</option>
                                <option
                                    v-for="area in areas"
                                    :key="area.id"
                                    :value="area.id"
                                >
                                    {{ area.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="sr-only" for="aot-user">Empleado</label>
                            <select
                                id="aot-user"
                                v-model="filters.user_id"
                                class="input"
                                @change="applyFilters"
                            >
                                <option value="">Todos los empleados</option>
                                <option
                                    v-for="emp in employees"
                                    :key="emp.id"
                                    :value="emp.id"
                                >
                                    {{ emp.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="sr-only" for="aot-month">Mes</label>
                            <select
                                id="aot-month"
                                v-model="filters.month"
                                class="input capitalize"
                                @change="applyFilters"
                            >
                                <option value="">Todos los meses</option>
                                <option
                                    v-for="m in MONTHS"
                                    :key="m.value"
                                    :value="m.value"
                                >
                                    {{ m.label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="sr-only" for="aot-year">Año</label>
                            <select
                                id="aot-year"
                                v-model="filters.year"
                                class="input"
                                @change="applyFilters"
                            >
                                <option v-for="y in YEARS" :key="y" :value="y">
                                    {{ y }}
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
                </template>

                <div
                    class="relative transition-opacity"
                    :class="loadingList ? 'pointer-events-none opacity-60' : ''"
                    :aria-busy="loadingList"
                >
                    <EmptyState
                        v-if="!rows.length"
                        :icon="Timer"
                        title="No hay registros de horas extra"
                        :description="
                            hasActiveFilters
                                ? 'Ningún registro coincide con los filtros seleccionados.'
                                : 'Cuando el equipo registre horas extra aparecerán aquí.'
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

                    <div v-else class="overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Empleado</th>
                                    <th>Fecha</th>
                                    <th class="text-right">Horas</th>
                                    <th>Proyecto</th>
                                    <th>Motivo</th>
                                    <th>Estado</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="ot in rows" :key="ot.id">
                                    <td>
                                        <p class="font-medium whitespace-nowrap">
                                            {{ ot.user?.name ?? '—' }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ ot.user?.area?.name || 'Sin área' }}
                                        </p>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        {{ formatDate(ot.date) }}
                                        <p
                                            class="text-xs text-muted-foreground tabular-nums"
                                        >
                                            {{
                                                formatTimeRange(
                                                    ot.start_time,
                                                    ot.end_time,
                                                )
                                            }}
                                        </p>
                                    </td>
                                    <td class="text-right font-medium tabular-nums">
                                        {{ formatHours(ot.hours) }}
                                    </td>
                                    <td>{{ ot.project || '—' }}</td>
                                    <td class="max-w-[260px]">
                                        <p class="truncate" :title="ot.reason || ''">
                                            {{ ot.reason || '—' }}
                                        </p>
                                    </td>
                                    <td><StatusBadge :status="ot.status" /></td>
                                    <td class="text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1">
                                            <template v-if="ot.status === 'pending'">
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    class="text-success hover:bg-success-soft hover:text-success"
                                                    :disabled="processingId === ot.id"
                                                    @click="approve(ot)"
                                                >
                                                    <Spinner
                                                        v-if="processingId === ot.id"
                                                    />
                                                    <Check v-else />
                                                    Aprobar
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    class="text-destructive hover:bg-destructive-soft hover:text-destructive"
                                                    :disabled="processingId === ot.id"
                                                    @click="openReject(ot)"
                                                >
                                                    <X />
                                                    Rechazar
                                                </Button>
                                            </template>
                                            <Button
                                                variant="ghost"
                                                size="icon-sm"
                                                :aria-label="`Editar registro de ${ot.user?.name ?? 'empleado'}`"
                                                title="Editar"
                                                @click="openEdit(ot)"
                                            >
                                                <Pencil />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <template v-if="overtimes.last_page > 1" #footer>
                    <TablePagination
                        class="w-full"
                        :page="overtimes.current_page"
                        :last-page="overtimes.last_page"
                        :total="overtimes.total"
                        @change="goToPage"
                    />
                </template>
            </SectionCard>
        </PageContainer>

        <OvertimeEditDialog
            :record="editingOT"
            :processing="savingEdit"
            :errors="editErrors"
            description="Los cambios recalculan las horas del registro."
            @close="editingOT = null"
            @submit="saveEdit"
        />

        <Dialog v-model:open="rejectOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Rechazar horas extra</DialogTitle>
                    <DialogDescription v-if="rejectingOT">
                        {{ rejectingOT.user?.name ?? 'Empleado' }} ·
                        {{ formatDate(rejectingOT.date) }} ·
                        {{ formatHours(rejectingOT.hours) }}
                    </DialogDescription>
                </DialogHeader>
                <form id="aot-reject-form" @submit.prevent="reject">
                    <FormField
                        label="Motivo del rechazo"
                        for="aot-reject-reason"
                        required
                        hint="El colaborador verá este motivo."
                        :error="rejectError"
                    >
                        <Textarea
                            id="aot-reject-reason"
                            v-model="rejectReason"
                            rows="3"
                            maxlength="500"
                            required
                        />
                    </FormField>
                </form>
                <DialogFooter>
                    <Button
                        variant="outline"
                        :disabled="rejecting"
                        @click="rejectingOT = null"
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="submit"
                        form="aot-reject-form"
                        variant="destructive"
                        :disabled="rejecting"
                    >
                        <Spinner v-if="rejecting" />
                        {{ rejecting ? 'Rechazando…' : 'Rechazar' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
