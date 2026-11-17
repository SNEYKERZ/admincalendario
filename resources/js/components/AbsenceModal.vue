<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { Check, RotateCcw, Trash2, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import FormField from '@/components/app/FormField.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
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
import { useConfirm } from '@/composables/useConfirm';
import { useNotification } from '@/composables/useNotification';
import { formatDays, formatHours } from '@/lib/format';

type ModalUser = {
    id: number;
    name: string;
};

type TypeOption = {
    id: number;
    name: string;
    counts_as_hours: boolean;
    default_include_saturday: boolean;
    default_include_sunday: boolean;
    default_include_holidays: boolean;
};

type CountryOption = {
    code: string;
    name: string;
};

type AbsenceRecord = {
    id: number;
    status: string;
    include_saturday: boolean;
    include_sunday: boolean;
    include_holidays: boolean;
    holiday_country?: string;
    reason?: string;
    start_datetime: string;
    end_datetime: string;
    total_days: number;
    total_hours: number;
    user?: {
        id: number;
        name: string;
        email: string;
        photo_path?: string;
    };
    type?: {
        id: number;
        name: string;
        counts_as_hours: boolean;
    };
    approver?: {
        id: number;
        name: string;
    };
    approved_at?: string;
};

type FormErrors = Partial<Record<string, string>>;

const notify = useNotification();
const confirm = useConfirm();
const page = usePage();

const formatToDatetimeLocal = (dateStr: string | Date) => {
    if (!dateStr) return '';

    const date = new Date(dateStr);
    const pad = (n: number) => n.toString().padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
};

const props = defineProps<{
    show: boolean;
    mode: string;
    absence?: AbsenceRecord | null;
    selectedRange?: { start: Date; end: Date } | null;
    users?: ModalUser[];
    isAdmin: boolean;
}>();

const emit = defineEmits(['close', 'saved']);

const getInitialForm = () => ({
    user_id: '' as string | number,
    absence_type_id: '' as string | number,
    start_datetime: '',
    end_datetime: '',
    include_saturday: true,
    include_sunday: true,
    include_holidays: true,
    holiday_country: 'CO',
    reason: '',
});

const form = ref(getInitialForm());
const errors = ref<FormErrors>({});
const types = ref<TypeOption[]>([]);
const countries = ref<CountryOption[]>([]);
const loadingOptions = ref(false);
/** Acción en curso (para mostrar el spinner en el botón correcto). */
const busy = ref<
    null | 'save' | 'update' | 'pendiente' | 'aprobado' | 'rechazado' | 'delete'
>(null);
const loading = computed(() => busy.value !== null);

const currentUserId = computed(() =>
    String((page.props as any).auth?.user?.id ?? ''),
);
const currentStatus = computed(() => props.absence?.status ?? 'pendiente');
const selectedType = computed(() =>
    types.value.find(
        (type) => String(type.id) === String(form.value.absence_type_id),
    ),
);
const showBusinessRules = computed(
    () => props.isAdmin && !selectedType.value?.counts_as_hours,
);

const canEdit = computed(() => {
    if (!props.absence) return true;
    const isOwner = props.absence.user?.id === parseInt(currentUserId.value);
    const isPending = props.absence.status === 'pendiente';
    return isOwner && isPending;
});

const loadTypes = async () => {
    try {
        const res = await axios.get('/absence-types');
        types.value = res.data;
    } catch {
        notify.error('No se pudieron cargar los tipos de ausencia.');
    }
};

const loadCountries = async () => {
    try {
        const res = await axios.get('/holidays/countries');
        countries.value = Object.entries(res.data).map(([code, name]) => ({
            code,
            name: name as string,
        }));
    } catch {
        notify.error('No se pudo cargar la lista de países.');
    }
};

watch(
    () => props.show,
    async (val) => {
        if (!val) return;

        form.value = getInitialForm();
        errors.value = {};
        loadingOptions.value = true;
        await Promise.all([loadTypes(), loadCountries()]);
        loadingOptions.value = false;

        if (!props.isAdmin) {
            form.value.user_id = currentUserId.value;
        }

        if (props.mode === 'create' && props.selectedRange) {
            const start = props.selectedRange.start;
            const end = new Date(props.selectedRange.end);

            end.setMinutes(end.getMinutes() - 1);

            form.value.start_datetime = formatToDatetimeLocal(start);
            form.value.end_datetime = formatToDatetimeLocal(end);
        }

        if (props.mode === 'view' && props.absence) {
            Object.assign(form.value, {
                user_id: props.absence.user?.id ?? '',
                absence_type_id: props.absence.type?.id ?? '',
                start_datetime: formatToDatetimeLocal(
                    props.absence.start_datetime,
                ),
                end_datetime: formatToDatetimeLocal(props.absence.end_datetime),
                include_saturday: props.absence.include_saturday,
                include_sunday: props.absence.include_sunday,
                include_holidays: props.absence.include_holidays,
                holiday_country: props.absence.holiday_country || 'CO',
                reason: props.absence.reason || '',
            });
        }
    },
);

const validateForm = () => {
    const next: FormErrors = {};

    if (props.isAdmin && props.mode === 'create' && !form.value.user_id) {
        next.user_id = 'Selecciona un colaborador.';
    }

    if (!form.value.absence_type_id) {
        next.absence_type_id = 'Selecciona el tipo de ausencia.';
    }

    if (!form.value.start_datetime) {
        next.start_datetime = 'Indica la fecha de inicio.';
    }

    if (!form.value.end_datetime) {
        next.end_datetime = 'Indica la fecha de fin.';
    } else if (
        form.value.start_datetime &&
        new Date(form.value.end_datetime) <= new Date(form.value.start_datetime)
    ) {
        next.end_datetime = 'La fecha de fin debe ser posterior al inicio.';
    }

    errors.value = next;
    return Object.keys(next).length === 0;
};

const handleError = (e: any, fallback: string) => {
    const serverErrors = e?.response?.data?.errors;
    if (serverErrors && typeof serverErrors === 'object') {
        const mapped: FormErrors = {};
        Object.entries(serverErrors).forEach(([key, value]) => {
            mapped[key] = Array.isArray(value)
                ? String(value[0])
                : String(value);
        });
        errors.value = mapped;
        // Errores que no corresponden a un campo visible se muestran como toast.
        const visible = [
            'user_id',
            'absence_type_id',
            'start_datetime',
            'end_datetime',
            'reason',
            'holiday_country',
        ];
        Object.entries(mapped)
            .filter(([key]) => !visible.includes(key))
            .forEach(([, msg]) => msg && notify.error(msg));
        return;
    }

    notify.error(e?.response?.data?.message || fallback);
};

const save = async () => {
    if (!validateForm()) return;

    busy.value = 'save';

    try {
        await axios.post('/absences', form.value);
        notify.success('Ausencia registrada correctamente.');
        emit('saved');
        emit('close');
    } catch (e: any) {
        handleError(e, 'No se pudo guardar la ausencia.');
    } finally {
        busy.value = null;
    }
};

const update = async () => {
    if (!validateForm()) return;

    busy.value = 'update';

    try {
        await axios.put(`/absences/${props.absence!.id}`, form.value);
        notify.success('Ausencia actualizada.');
        emit('saved');
        emit('close');
    } catch (e: any) {
        handleError(e, 'No se pudo actualizar la ausencia.');
    } finally {
        busy.value = null;
    }
};

const decisionNote = ref('');

watch(
    () => props.absence?.id,
    () => {
        decisionNote.value = '';
    },
);

const statusMessages: Record<string, string> = {
    pendiente: 'La ausencia volvió a estado pendiente.',
    aprobado: 'Ausencia aprobada.',
    rechazado: 'Ausencia rechazada.',
};

const changeStatus = async (status: 'aprobado' | 'rechazado' | 'pendiente') => {
    if (!props.absence) return;

    if (status === 'rechazado') {
        const ok = await confirm({
            title: 'Rechazar ausencia',
            description: `Se notificará a ${props.absence.user?.name ?? 'la persona'} que su solicitud fue rechazada.`,
            confirmText: 'Rechazar',
            variant: 'destructive',
        });
        if (!ok) return;
    }

    const statusMap: Record<string, string> = {
        pendiente: 'pending',
        aprobado: 'approve',
        rechazado: 'reject',
    };

    busy.value = status;

    try {
        const note = decisionNote.value.trim();
        const payload =
            status === 'aprobado'
                ? { notes: note || null }
                : status === 'rechazado'
                  ? { reason: note || null }
                  : {};
        await axios.post(
            `/absences/${props.absence.id}/${statusMap[status]}`,
            payload,
        );
        notify.success(statusMessages[status]);
        emit('saved');
        emit('close');
    } catch (e: any) {
        handleError(e, 'No se pudo cambiar el estado.');
        // El estado pudo cambiar en el servidor (p. ej. ya no está pendiente):
        // refrescamos la vista para no quedar en un estado inconsistente.
        if ([403, 409, 422].includes(e?.response?.status)) emit('saved');
    } finally {
        busy.value = null;
    }
};

const remove = async () => {
    if (!props.absence) return;

    const ok = await confirm({
        title: 'Eliminar ausencia',
        description:
            'La ausencia se eliminará de forma permanente y se recalculará el saldo de días. Esta acción no se puede deshacer.',
        confirmText: 'Eliminar',
        variant: 'destructive',
    });
    if (!ok) return;

    busy.value = 'delete';

    try {
        await axios.delete(`/absences/${props.absence.id}`);
        notify.success('Ausencia eliminada.');
        emit('saved');
        emit('close');
    } catch (e: any) {
        notify.error(
            e?.response?.data?.message || 'No se pudo eliminar la ausencia.',
        );
    } finally {
        busy.value = null;
    }
};

const onOpenChange = (open: boolean) => {
    if (!open && !loading.value) emit('close');
};

const fieldClass = (key: string) =>
    errors.value[key] ? 'input border-destructive' : 'input';
</script>

<template>
    <Dialog :open="show" @update:open="onOpenChange">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>
                    {{
                        mode === 'create'
                            ? 'Nueva ausencia o novedad'
                            : 'Detalle de la ausencia'
                    }}
                </DialogTitle>
                <DialogDescription v-if="mode === 'create'">
                    Registra el tipo, las fechas y el motivo. Quedará pendiente
                    de aprobación.
                </DialogDescription>
                <DialogDescription
                    v-else
                    class="flex items-center gap-2"
                    as="div"
                >
                    Estado actual:
                    <StatusBadge :status="currentStatus" />
                </DialogDescription>
            </DialogHeader>

            <!-- Resumen (solo en modo detalle) -->
            <dl
                v-if="mode === 'view' && absence?.user"
                class="grid gap-x-6 gap-y-3 rounded-lg border bg-muted/40 p-4 text-sm sm:grid-cols-2"
            >
                <div>
                    <dt class="text-xs text-muted-foreground">Colaborador</dt>
                    <dd class="font-medium">{{ absence.user.name }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-xs text-muted-foreground">Correo</dt>
                    <dd class="truncate">{{ absence.user.email }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">Tipo</dt>
                    <dd class="font-medium">{{ absence.type?.name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">Duración</dt>
                    <dd class="font-medium tabular-nums">
                        {{ formatDays(absence.total_days) }} ·
                        {{ formatHours(absence.total_hours) }}
                    </dd>
                </div>
                <div v-if="absence.approved_at" class="sm:col-span-2">
                    <dt class="text-xs text-muted-foreground">Aprobada por</dt>
                    <dd>
                        <span class="font-medium">{{
                            absence.approver?.name ?? 'Sistema'
                        }}</span>
                        el
                        {{
                            new Date(absence.approved_at).toLocaleDateString(
                                'es-CO',
                            )
                        }}
                    </dd>
                </div>
            </dl>

            <form
                id="absence-form"
                class="grid gap-4"
                novalidate
                @submit.prevent="mode === 'create' ? save() : update()"
            >
                <FormField
                    v-if="isAdmin && mode === 'create'"
                    label="Colaborador"
                    for="absence-user"
                    :error="errors.user_id"
                    required
                >
                    <select
                        id="absence-user"
                        v-model="form.user_id"
                        :class="fieldClass('user_id')"
                        :disabled="!canEdit"
                        :aria-invalid="!!errors.user_id"
                    >
                        <option value="">Selecciona un colaborador</option>
                        <option
                            v-for="u in users ?? []"
                            :key="u.id"
                            :value="u.id"
                        >
                            {{ u.name }}
                        </option>
                    </select>
                </FormField>

                <FormField
                    label="Tipo de ausencia"
                    for="absence-type"
                    :error="errors.absence_type_id"
                    required
                >
                    <select
                        id="absence-type"
                        v-model="form.absence_type_id"
                        :class="fieldClass('absence_type_id')"
                        :disabled="!canEdit || loadingOptions"
                        :aria-invalid="!!errors.absence_type_id"
                    >
                        <option value="">
                            {{
                                loadingOptions
                                    ? 'Cargando tipos…'
                                    : 'Selecciona un tipo'
                            }}
                        </option>
                        <option v-for="t in types" :key="t.id" :value="t.id">
                            {{ t.name }}
                        </option>
                    </select>
                </FormField>

                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField
                        label="Inicio"
                        for="absence-start"
                        :error="errors.start_datetime"
                        required
                    >
                        <input
                            id="absence-start"
                            v-model="form.start_datetime"
                            type="datetime-local"
                            :class="fieldClass('start_datetime')"
                            :disabled="!canEdit"
                            :aria-invalid="!!errors.start_datetime"
                        />
                    </FormField>

                    <FormField
                        label="Fin"
                        for="absence-end"
                        :error="errors.end_datetime"
                        required
                    >
                        <input
                            id="absence-end"
                            v-model="form.end_datetime"
                            type="datetime-local"
                            :class="fieldClass('end_datetime')"
                            :disabled="!canEdit"
                            :aria-invalid="!!errors.end_datetime"
                        />
                    </FormField>
                </div>

                <fieldset
                    v-if="showBusinessRules"
                    class="rounded-lg border bg-muted/30 p-4"
                >
                    <legend class="px-1 text-[13px] font-medium">
                        Descontar del saldo de días
                    </legend>
                    <div class="flex flex-wrap gap-x-6 gap-y-2">
                        <label class="flex items-center gap-2 text-sm">
                            <input
                                v-model="form.include_saturday"
                                type="checkbox"
                                class="size-4 accent-primary"
                                :disabled="!canEdit"
                            />
                            Sábados
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input
                                v-model="form.include_sunday"
                                type="checkbox"
                                class="size-4 accent-primary"
                                :disabled="!canEdit"
                            />
                            Domingos
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input
                                v-model="form.include_holidays"
                                type="checkbox"
                                class="size-4 accent-primary"
                                :disabled="!canEdit"
                            />
                            Festivos
                        </label>
                    </div>
                    <FormField
                        v-if="form.include_holidays"
                        label="País para festivos"
                        for="absence-country"
                        :error="errors.holiday_country"
                        class="mt-3 sm:max-w-xs"
                    >
                        <select
                            id="absence-country"
                            v-model="form.holiday_country"
                            class="input"
                            :disabled="!canEdit"
                        >
                            <option
                                v-for="country in countries"
                                :key="country.code"
                                :value="country.code"
                            >
                                {{ country.name }}
                            </option>
                        </select>
                    </FormField>
                    <p class="mt-2 text-xs text-muted-foreground">
                        Los días marcados se descuentan del saldo de días
                        disponibles.
                    </p>
                </fieldset>

                <FormField
                    label="Motivo"
                    for="absence-reason"
                    :error="errors.reason"
                >
                    <textarea
                        id="absence-reason"
                        v-model="form.reason"
                        class="input min-h-24"
                        placeholder="Opcional: añade contexto para quien aprueba"
                        :disabled="!canEdit"
                    />
                </FormField>
            </form>

            <FormField
                v-if="mode === 'view' && isAdmin && absence"
                label="Comentario de la decisión (opcional)"
                for="absence-decision-note"
                hint="Se envía como nota al aprobar o como motivo al rechazar. Recomendado al rechazar."
            >
                <textarea
                    id="absence-decision-note"
                    v-model="decisionNote"
                    class="input min-h-16"
                    maxlength="500"
                    placeholder="Ej.: Coincide con el cierre de mes"
                    :disabled="loading"
                />
            </FormField>

            <DialogFooter
                class="flex-col-reverse gap-2 sm:flex-row sm:justify-between"
            >
                <div class="flex flex-wrap gap-2">
                    <Button
                        v-if="mode === 'view' && isAdmin && absence"
                        variant="ghost"
                        class="text-destructive hover:bg-destructive-soft hover:text-destructive"
                        :disabled="loading"
                        @click="remove"
                    >
                        <Spinner v-if="busy === 'delete'" />
                        <Trash2 v-else />
                        Eliminar
                    </Button>
                </div>
                <div class="flex flex-wrap justify-end gap-2">
                    <Button
                        variant="outline"
                        :disabled="loading"
                        @click="emit('close')"
                    >
                        Cerrar
                    </Button>

                    <template v-if="mode === 'view' && isAdmin && absence">
                        <Button
                            v-if="currentStatus !== 'pendiente'"
                            variant="outline"
                            :disabled="loading"
                            @click="changeStatus('pendiente')"
                        >
                            <Spinner v-if="busy === 'pendiente'" />
                            <RotateCcw v-else />
                            Volver a pendiente
                        </Button>
                        <Button
                            v-if="currentStatus !== 'rechazado'"
                            variant="outline"
                            class="border-destructive/30 text-destructive hover:bg-destructive-soft hover:text-destructive"
                            :disabled="loading"
                            @click="changeStatus('rechazado')"
                        >
                            <Spinner v-if="busy === 'rechazado'" />
                            <X v-else />
                            Rechazar
                        </Button>
                        <Button
                            v-if="currentStatus !== 'aprobado'"
                            class="bg-success text-success-foreground hover:bg-success/90"
                            :disabled="loading"
                            @click="changeStatus('aprobado')"
                        >
                            <Spinner v-if="busy === 'aprobado'" />
                            <Check v-else />
                            Aprobar
                        </Button>
                    </template>

                    <Button
                        v-if="mode === 'view' && canEdit"
                        type="submit"
                        form="absence-form"
                        :disabled="loading"
                    >
                        <Spinner v-if="busy === 'update'" />
                        Guardar cambios
                    </Button>

                    <Button
                        v-if="mode === 'create'"
                        type="submit"
                        form="absence-form"
                        :disabled="loading || loadingOptions"
                    >
                        <Spinner v-if="busy === 'save'" />
                        {{
                            busy === 'save'
                                ? 'Guardando…'
                                : 'Registrar ausencia'
                        }}
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
