<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { Clock, ListPlus, Pencil, Plus, Send, Trash2, X } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import EmptyState from '@/components/app/EmptyState.vue';
import FormField from '@/components/app/FormField.vue';
import PageContainer from '@/components/app/PageContainer.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import SectionCard from '@/components/app/SectionCard.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import TablePagination from '@/components/app/TablePagination.vue';
import OvertimeEditDialog from '@/components/overtime/OvertimeEditDialog.vue';
import {
    calcHours,
    firstErrorMessage,
    formatDate,
    formatHours,
    formatTimeRange,
    MONTHS,
    YEARS
    
    
} from '@/components/overtime/utils';
import type {OvertimeFormValues, OvertimeRecord} from '@/components/overtime/utils';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useConfirm } from '@/composables/useConfirm';
import { useNotification } from '@/composables/useNotification';
import AppLayout from '@/layouts/AppLayout.vue';

type DraftRecord = {
    user_id: number | '';
    user_name: string;
    date: string;
    start_time: string;
    end_time: string;
    project: string;
    client: string;
    reason: string;
    hours: number;
};

type Employee = { id: number; name: string };

const page = usePage();
const notify = useNotification();
const confirm = useConfirm();

const authUser = computed(() => (page.props as any).auth?.user ?? {});
const isAdmin = computed(() =>
    ['admin', 'superadmin'].includes(authUser.value?.role),
);

const today = () => {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

const emptyForm = () => ({
    user_id: '' as number | '',
    date: today(),
    start_time: '',
    end_time: '',
    project: '',
    client: '',
    reason: '',
});

const form = ref(emptyForm());
const formErrors = ref<Record<string, string>>({});
const draftRecords = ref<DraftRecord[]>([]);
const savingDraft = ref(false);

const employees = ref<Employee[]>([]);

const records = ref<OvertimeRecord[]>([]);
const loadingRecords = ref(true);
const recordsError = ref('');
const pagination = ref({ current_page: 1, last_page: 1, total: 0 });
const filterMonth = ref('');
const filterYear = ref(String(new Date().getFullYear()));

const editingRecord = ref<OvertimeRecord | null>(null);
const editErrors = ref<Record<string, string>>({});
const savingEdit = ref(false);
const deletingId = ref<number | null>(null);

const calculatedHours = computed(() =>
    calcHours(form.value.start_time, form.value.end_time),
);

const draftTotal = computed(() =>
    draftRecords.value.reduce((sum, r) => sum + r.hours, 0),
);

/** Normaliza la respuesta del API (paginador de Laravel o arreglo plano). */
const normalizeList = (payload: any): OvertimeRecord[] => {
    if (Array.isArray(payload)) return payload;
    if (Array.isArray(payload?.data)) return payload.data;
    return [];
};

const periodRange = () => {
    const y = filterYear.value;
    if (!filterMonth.value) return { start: `${y}-01-01`, end: `${y}-12-31` };
    const m = Number(filterMonth.value);
    const lastDay = new Date(Number(y), m, 0).getDate();
    return {
        start: `${y}-${filterMonth.value}-01`,
        end: `${y}-${filterMonth.value}-${String(lastDay).padStart(2, '0')}`,
    };
};

const fetchRecords = async (pageNumber = 1) => {
    loadingRecords.value = true;
    recordsError.value = '';
    const { start, end } = periodRange();
    try {
        const { data } = await axios.get('/api/overtime-hours', {
            params: { start_date: start, end_date: end, page: pageNumber },
        });
        records.value = normalizeList(data);
        pagination.value = {
            current_page: Number(data?.current_page ?? 1),
            last_page: Number(data?.last_page ?? 1),
            total: Number(data?.total ?? records.value.length),
        };
    } catch (e) {
        console.error(e);
        records.value = [];
        recordsError.value = 'No se pudieron cargar tus registros.';
    } finally {
        loadingRecords.value = false;
    }
};

const fetchEmployees = async () => {
    if (!isAdmin.value) return;
    try {
        const { data } = await axios.get('/api/users/employees');
        employees.value = Array.isArray(data) ? data : (data?.data ?? []);
    } catch (e) {
        console.error(e);
    }
};

const addToDraft = () => {
    formErrors.value = {};
    if (calculatedHours.value <= 0) {
        formErrors.value.end_time =
            'La hora de fin debe ser posterior a la de inicio.';
        return;
    }
    if (form.value.reason.trim().length < 10) {
        formErrors.value.reason = 'El motivo debe tener al menos 10 caracteres.';
        return;
    }
    const targetId = form.value.user_id;
    if (
        draftRecords.value.some(
            (r) => r.date === form.value.date && r.user_id === targetId,
        )
    ) {
        formErrors.value.date =
            'Ya hay un registro para esta fecha en la lista por enviar.';
        return;
    }

    const userName = targetId
        ? (employees.value.find((e) => e.id === targetId)?.name ?? '')
        : 'Yo';

    draftRecords.value.push({
        ...form.value,
        user_id: targetId,
        user_name: userName,
        hours: calculatedHours.value,
    });

    const keepUser = form.value.user_id;
    form.value = { ...emptyForm(), user_id: keepUser };
};

const buildPayloadRecord = (r: DraftRecord) => {
    const record: Record<string, unknown> = {
        // El backend lee records.*.user_id siempre: se envía el propio id cuando es "para mí".
        user_id: r.user_id || authUser.value?.id,
        date: r.date,
        start_time: r.start_time,
        end_time: r.end_time,
        reason: r.reason.trim(),
    };
    if (r.project.trim()) record.project = r.project.trim();
    if (r.client.trim()) record.client = r.client.trim();
    return record;
};

const saveDraft = async () => {
    if (!draftRecords.value.length) return;
    savingDraft.value = true;
    formErrors.value = {};
    try {
        await axios.post('/api/overtime-hours/batch', {
            records: draftRecords.value.map(buildPayloadRecord),
        });
        const count = draftRecords.value.length;
        draftRecords.value = [];
        notify.success(
            count === 1
                ? 'Registro enviado para aprobación.'
                : `${count} registros enviados para aprobación.`,
        );
        fetchRecords(1);
    } catch (e: any) {
        const message = firstErrorMessage(
            e,
            'No se pudieron guardar los registros.',
        );
        formErrors.value.general = message;
        notify.error(message);
    } finally {
        savingDraft.value = false;
    }
};

const clearDraft = async () => {
    const ok = await confirm({
        title: 'Vaciar lista',
        description: `Se descartarán ${draftRecords.value.length} registros que aún no se han enviado.`,
        confirmText: 'Vaciar lista',
        variant: 'destructive',
    });
    if (ok) draftRecords.value = [];
};

const applyFilters = () => fetchRecords(1);

const openEdit = (record: OvertimeRecord) => {
    editErrors.value = {};
    editingRecord.value = record;
};

const saveEdit = async (values: OvertimeFormValues) => {
    if (!editingRecord.value) return;
    savingEdit.value = true;
    editErrors.value = {};
    const payload: Record<string, unknown> = {
        date: values.date,
        start_time: values.start_time,
        end_time: values.end_time,
        reason: values.reason.trim(),
    };
    if (values.project.trim()) payload.project = values.project.trim();
    if (values.client.trim()) payload.client = values.client.trim();
    try {
        await axios.put(
            `/api/overtime-hours/${editingRecord.value.id}`,
            payload,
        );
        editingRecord.value = null;
        notify.success('Registro actualizado.');
        fetchRecords(pagination.value.current_page);
    } catch (e: any) {
        const errors = e?.response?.data?.errors;
        if (errors) {
            editErrors.value = Object.fromEntries(
                Object.entries(errors).map(([k, v]) => [
                    k,
                    Array.isArray(v) ? String(v[0]) : String(v),
                ]),
            );
        } else {
            editErrors.value = {
                general: firstErrorMessage(e, 'No se pudo actualizar el registro.'),
            };
        }
    } finally {
        savingEdit.value = false;
    }
};

const deleteRecord = async (record: OvertimeRecord) => {
    const ok = await confirm({
        title: 'Eliminar registro',
        description: `Se eliminará el registro del ${formatDate(record.date)} (${formatHours(record.hours)}). Esta acción no se puede deshacer.`,
        variant: 'destructive',
    });
    if (!ok) return;
    deletingId.value = record.id;
    try {
        await axios.delete(`/api/overtime-hours/${record.id}`);
        notify.success('Registro eliminado.');
        fetchRecords(pagination.value.current_page);
    } catch (e: any) {
        notify.error(firstErrorMessage(e, 'No se pudo eliminar el registro.'));
    } finally {
        deletingId.value = null;
    }
};

onMounted(() => {
    fetchEmployees();
    fetchRecords();
});
</script>

<template>
    <Head title="Mis horas extra" />
    <AppLayout :breadcrumbs="[{ title: 'Mis horas extra', href: '/overtime-hours' }]">
        <PageContainer>
            <PageHeader
                title="Mis horas extra"
                description="Registra tus horas extra, revísalas en la lista y envíalas juntas para aprobación."
                :icon="Clock"
            />

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,420px)]">
                <!-- Captura -->
                <SectionCard
                    title="Registrar horas extra"
                    description="Los registros se añaden a una lista y no se envían hasta que pulses «Enviar»."
                >
                    <form class="grid gap-4" @submit.prevent="addToDraft">
                        <FormField
                            v-if="isAdmin"
                            label="Registrar para"
                            for="ot-user"
                        >
                            <select
                                id="ot-user"
                                v-model="form.user_id"
                                class="input"
                            >
                                <option value="">Para mí</option>
                                <option
                                    v-for="emp in employees"
                                    :key="emp.id"
                                    :value="emp.id"
                                >
                                    {{ emp.name }}
                                </option>
                            </select>
                        </FormField>

                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <FormField
                                label="Fecha"
                                for="ot-date"
                                required
                                :error="formErrors.date"
                            >
                                <Input
                                    id="ot-date"
                                    v-model="form.date"
                                    type="date"
                                    required
                                />
                            </FormField>
                            <FormField
                                label="Hora de inicio"
                                for="ot-start"
                                required
                                :error="formErrors.start_time"
                            >
                                <Input
                                    id="ot-start"
                                    v-model="form.start_time"
                                    type="time"
                                    required
                                />
                            </FormField>
                            <FormField
                                label="Hora de fin"
                                for="ot-end"
                                required
                                :error="formErrors.end_time"
                            >
                                <Input
                                    id="ot-end"
                                    v-model="form.end_time"
                                    type="time"
                                    required
                                />
                            </FormField>
                            <div class="grid content-start gap-1.5">
                                <span class="text-[13px] font-medium">Total</span>
                                <output
                                    for="ot-start ot-end"
                                    aria-live="polite"
                                    class="flex h-9 items-center rounded-md border border-dashed bg-muted/50 px-3 text-sm font-semibold tabular-nums"
                                    :class="
                                        calculatedHours > 0
                                            ? 'text-foreground'
                                            : 'text-muted-foreground'
                                    "
                                >
                                    {{ formatHours(Math.max(calculatedHours, 0)) }}
                                </output>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <FormField label="Proyecto" for="ot-project">
                                <Input
                                    id="ot-project"
                                    v-model="form.project"
                                    placeholder="Código o nombre (opcional)"
                                />
                            </FormField>
                            <FormField label="Cliente" for="ot-client">
                                <Input
                                    id="ot-client"
                                    v-model="form.client"
                                    placeholder="Nombre del cliente (opcional)"
                                />
                            </FormField>
                        </div>

                        <FormField
                            label="Motivo"
                            for="ot-reason"
                            required
                            :error="formErrors.reason"
                            :hint="`Mínimo 10 caracteres · ${form.reason.trim().length} escritos`"
                        >
                            <Textarea
                                id="ot-reason"
                                v-model="form.reason"
                                rows="3"
                                placeholder="Describe la tarea realizada"
                                required
                            />
                        </FormField>

                        <div class="flex justify-end">
                            <Button type="submit" variant="outline">
                                <ListPlus />
                                Añadir a la lista
                            </Button>
                        </div>
                    </form>
                </SectionCard>

                <!-- Lista por enviar -->
                <SectionCard
                    title="Por enviar"
                    :description="
                        draftRecords.length
                            ? `${draftRecords.length} registro(s) · ${formatHours(draftTotal)}`
                            : 'Aún no has añadido registros.'
                    "
                    flush
                    class="self-start"
                >
                    <EmptyState
                        v-if="!draftRecords.length"
                        compact
                        :icon="Plus"
                        title="Lista vacía"
                        description="Completa el formulario y pulsa «Añadir a la lista»."
                    />
                    <ul v-else class="divide-y">
                        <li
                            v-for="(r, i) in draftRecords"
                            :key="`${r.date}-${r.user_id}-${i}`"
                            class="flex items-start gap-3 px-4 py-3 sm:px-5"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium">
                                    {{ formatDate(r.date) }}
                                    <span class="text-muted-foreground">
                                        · {{ formatTimeRange(r.start_time, r.end_time) }}
                                    </span>
                                </p>
                                <p class="truncate text-xs text-muted-foreground">
                                    <template v-if="isAdmin">{{ r.user_name }} · </template>
                                    {{ r.project || 'Sin proyecto' }} · {{ r.reason }}
                                </p>
                            </div>
                            <span class="text-sm font-semibold tabular-nums">{{
                                formatHours(r.hours)
                            }}</span>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                :aria-label="`Quitar registro del ${formatDate(r.date)}`"
                                @click="draftRecords.splice(i, 1)"
                            >
                                <X />
                            </Button>
                        </li>
                    </ul>
                    <template v-if="draftRecords.length" #footer>
                        <p
                            v-if="formErrors.general"
                            class="text-sm text-destructive"
                            role="alert"
                        >
                            {{ formErrors.general }}
                        </p>
                        <div class="flex w-full gap-2 sm:justify-end">
                            <Button
                                variant="ghost"
                                :disabled="savingDraft"
                                @click="clearDraft"
                            >
                                Vaciar
                            </Button>
                            <Button
                                class="flex-1 sm:flex-none"
                                :disabled="savingDraft"
                                @click="saveDraft"
                            >
                                <Spinner v-if="savingDraft" />
                                <Send v-else />
                                {{
                                    savingDraft
                                        ? 'Enviando…'
                                        : `Enviar ${draftRecords.length} para aprobación`
                                }}
                            </Button>
                        </div>
                    </template>
                </SectionCard>
            </div>

            <!-- Registros -->
            <SectionCard title="Mis registros" flush>
                <template #actions>
                    <label class="sr-only" for="ot-filter-month">Mes</label>
                    <select
                        id="ot-filter-month"
                        v-model="filterMonth"
                        class="input h-8 w-40 capitalize"
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
                    <label class="sr-only" for="ot-filter-year">Año</label>
                    <select
                        id="ot-filter-year"
                        v-model="filterYear"
                        class="input h-8 w-28"
                        @change="applyFilters"
                    >
                        <option v-for="y in YEARS" :key="y" :value="y">
                            {{ y }}
                        </option>
                    </select>
                </template>

                <div v-if="loadingRecords" class="space-y-2 p-4 sm:p-5">
                    <Skeleton v-for="n in 4" :key="n" class="h-9 w-full" />
                </div>
                <EmptyState
                    v-else-if="recordsError"
                    compact
                    title="Error al cargar"
                    :description="recordsError"
                >
                    <Button variant="outline" size="sm" @click="fetchRecords(1)">
                        Reintentar
                    </Button>
                </EmptyState>
                <EmptyState
                    v-else-if="!records.length"
                    :icon="Clock"
                    title="Sin registros en este periodo"
                    description="Cambia el mes o el año, o registra nuevas horas extra arriba."
                />
                <div v-else class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Horario</th>
                                <th class="text-right">Horas</th>
                                <th>Proyecto</th>
                                <th>Motivo</th>
                                <th>Estado</th>
                                <th class="text-right">
                                    <span class="sr-only">Acciones</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="r in records" :key="r.id">
                                <td class="whitespace-nowrap">
                                    {{ formatDate(r.date) }}
                                </td>
                                <td
                                    class="whitespace-nowrap text-muted-foreground tabular-nums"
                                >
                                    {{ formatTimeRange(r.start_time, r.end_time) }}
                                </td>
                                <td class="text-right font-medium tabular-nums">
                                    {{ formatHours(r.hours) }}
                                </td>
                                <td>{{ r.project || '—' }}</td>
                                <td class="max-w-[280px]">
                                    <p class="truncate" :title="r.reason || ''">
                                        {{ r.reason || '—' }}
                                    </p>
                                    <p
                                        v-if="r.status === 'rejected' && r.approval_notes"
                                        class="truncate text-xs text-destructive"
                                        :title="r.approval_notes"
                                    >
                                        Motivo del rechazo: {{ r.approval_notes }}
                                    </p>
                                </td>
                                <td><StatusBadge :status="r.status" /></td>
                                <td class="text-right whitespace-nowrap">
                                    <div
                                        v-if="r.status === 'pending'"
                                        class="inline-flex gap-1"
                                    >
                                        <Button
                                            variant="ghost"
                                            size="icon-sm"
                                            :aria-label="`Editar registro del ${formatDate(r.date)}`"
                                            title="Editar"
                                            @click="openEdit(r)"
                                        >
                                            <Pencil />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon-sm"
                                            class="text-destructive hover:bg-destructive-soft hover:text-destructive"
                                            :aria-label="`Eliminar registro del ${formatDate(r.date)}`"
                                            title="Eliminar"
                                            :disabled="deletingId === r.id"
                                            @click="deleteRecord(r)"
                                        >
                                            <Spinner v-if="deletingId === r.id" />
                                            <Trash2 v-else />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <template
                    v-if="!loadingRecords && pagination.last_page > 1"
                    #footer
                >
                    <TablePagination
                        class="w-full"
                        :page="pagination.current_page"
                        :last-page="pagination.last_page"
                        :total="pagination.total"
                        @change="fetchRecords"
                    />
                </template>
            </SectionCard>
        </PageContainer>

        <OvertimeEditDialog
            :record="editingRecord"
            :processing="savingEdit"
            :errors="editErrors"
            @close="editingRecord = null"
            @submit="saveEdit"
        />
    </AppLayout>
</template>
