<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import ConfirmDialog from './ConfirmDialog.vue';

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
    user?: { id: number };
    type?: { id: number };
};

const toast = useToast();
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
    user_id: '',
    absence_type_id: '',
    start_datetime: '',
    end_datetime: '',
    include_saturday: true,
    include_sunday: true,
    include_holidays: true,
    holiday_country: 'CO',
    reason: '',
});

const form = ref(getInitialForm());
const types = ref<TypeOption[]>([]);
const countries = ref<CountryOption[]>([]);
const loading = ref(false);
const currentUserId = computed(() => String(page.props.auth?.user?.id ?? ''));
const currentStatus = computed(() => props.absence?.status ?? 'pendiente');
const selectedType = computed(() =>
    types.value.find(
        (type) => String(type.id) === String(form.value.absence_type_id),
    ),
);
const showBusinessRules = computed(
    () =>
        props.isAdmin &&
        !selectedType.value?.counts_as_hours &&
        form.value.include_holidays,
);

const canEdit = computed(() => {
    if (!props.absence) return true;
    return props.isAdmin || props.absence.status === 'pendiente';
});

const loadTypes = async () => {
    try {
        const res = await axios.get('/absence-types');
        types.value = res.data;
    } catch {
        toast.error('Error cargando tipos');
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
        toast.error('Error cargando países');
    }
};

watch(
    () => form.value.absence_type_id,
    () => {
        // Ya no se aplican los defaults del tipo
    },
);

watch(
    () => props.show,
    async (val) => {
        if (!val) return;

        form.value = getInitialForm();
        await Promise.all([loadTypes(), loadCountries()]);

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
                user_id: props.absence.user?.id,
                absence_type_id: props.absence.type?.id,
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
    if (props.isAdmin && !form.value.user_id) {
        toast.error('Selecciona un usuario');
        return false;
    }

    if (!form.value.absence_type_id) {
        toast.error('Selecciona un tipo');
        return false;
    }

    const start = new Date(form.value.start_datetime);
    const end = new Date(form.value.end_datetime);

    if (end <= start) {
        toast.error('Fechas invalidas');
        return false;
    }

    return true;
};

const save = async () => {
    if (!validateForm()) return;

    loading.value = true;

    try {
        await axios.post('/absences', form.value);
        toast.success('Ausencia creada');
        emit('saved');
        emit('close');
    } catch (e: any) {
        if (e.response?.data?.errors) {
            Object.values(e.response.data.errors)
                .flat()
                .forEach((msg: any) => toast.error(msg));
        } else {
            toast.error('Error al guardar');
        }
    } finally {
        loading.value = false;
    }
};

const update = async () => {
    if (!validateForm()) return;

    loading.value = true;

    try {
        await axios.put(`/absences/${props.absence!.id}`, form.value);
        toast.success('Actualizado correctamente');
        emit('saved');
        emit('close');
    } catch (e: any) {
        if (e.response?.data?.errors) {
            Object.values(e.response.data.errors)
                .flat()
                .forEach((msg: any) => toast.error(msg));
        } else {
            toast.error('Error al actualizar');
        }
    } finally {
        loading.value = false;
    }
};

const changeStatus = async (status: 'aprobado' | 'rechazado' | 'pendiente') => {
    if (!props.absence) return;
    const endpoint = status === 'pendiente' ? 'pending' : status;

    loading.value = true;

    try {
        await axios.post(`/absences/${props.absence.id}/${endpoint}`);
        toast.success(`Estado cambiado a ${status}`);
        emit('saved');
        emit('close');
    } catch (e: any) {
        if (e.response?.data?.errors) {
            Object.values(e.response.data.errors)
                .flat()
                .forEach((msg: any) => toast.error(msg));
        } else {
            toast.error('Error al cambiar estado');
        }
    } finally {
        loading.value = false;
    }
};

const remove = async () => {
    if (!props.absence) return;

    loading.value = true;

    try {
        await axios.delete(`/absences/${props.absence.id}`);
        toast.success('Ausencia eliminada');
        emit('saved');
        emit('close');
    } catch {
        toast.error('Error al eliminar');
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
    >
        <div
            class="w-full max-w-2xl space-y-4 rounded-xl bg-white p-6 shadow-xl dark:bg-gray-900"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2
                        class="text-lg font-bold text-gray-900 dark:text-gray-100"
                    >
                        {{
                            mode === 'create'
                                ? 'Nueva ausencia'
                                : 'Detalle de ausencia'
                        }}
                    </h2>
                    <p
                        v-if="mode === 'view'"
                        class="text-sm text-gray-500 dark:text-gray-400"
                    >
                        Estado actual:
                        <span class="font-medium capitalize">{{
                            currentStatus
                        }}</span>
                    </p>
                </div>
            </div>

            <div v-if="isAdmin" class="space-y-1">
                <label class="text-sm text-gray-600 dark:text-gray-300"
                    >Usuario</label
                >
                <select
                    v-model="form.user_id"
                    class="input"
                    :disabled="!canEdit"
                >
                    <option value="">Seleccionar usuario</option>
                    <option v-for="u in users ?? []" :key="u.id" :value="u.id">
                        {{ u.name }}
                    </option>
                </select>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-300"
                        >Tipo</label
                    >
                    <select
                        v-model="form.absence_type_id"
                        class="input"
                        :disabled="!canEdit"
                    >
                        <option value="">Seleccione tipo</option>
                        <option v-for="t in types" :key="t.id" :value="t.id">
                            {{ t.name }}
                        </option>
                    </select>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-300"
                            >Inicio</label
                        >
                        <input
                            type="datetime-local"
                            v-model="form.start_datetime"
                            class="input"
                            :disabled="!canEdit"
                        />
                    </div>

                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-300"
                            >Fin</label
                        >
                        <input
                            type="datetime-local"
                            v-model="form.end_datetime"
                            class="input"
                            :disabled="!canEdit"
                        />
                    </div>
                </div>

                <div
                    v-if="showBusinessRules"
                    class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-950/20"
                >
                    <p
                        class="mb-3 text-sm font-medium text-amber-900 dark:text-amber-200"
                    >
                        Descontar del saldo de días
                    </p>
                    <div class="grid gap-3 md:grid-cols-3">
                        <label
                            class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200"
                        >
                            <input
                                v-model="form.include_saturday"
                                type="checkbox"
                                :disabled="!canEdit"
                            />
                            Sábados
                        </label>
                        <label
                            class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200"
                        >
                            <input
                                v-model="form.include_sunday"
                                type="checkbox"
                                :disabled="!canEdit"
                            />
                            Domingos
                        </label>
                        <label
                            class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200"
                        >
                            <input
                                v-model="form.include_holidays"
                                type="checkbox"
                                :disabled="!canEdit"
                            />
                            Festivos
                        </label>
                    </div>
                    <!-- Country selector for holidays -->
                    <div v-if="form.include_holidays" class="mt-3">
                        <label class="text-xs text-gray-500 dark:text-gray-400">
                            País para festividades:
                        </label>
                        <select
                            v-model="form.holiday_country"
                            class="input mt-1 text-sm"
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
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Si están marcados, estos días se descontarán del saldo
                        de días disponibles.
                    </p>
                </div>

                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-300"
                        >Motivo</label
                    >
                    <textarea
                        v-model="form.reason"
                        class="input min-h-24"
                        :disabled="!canEdit"
                    />
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-2 pt-2">
                <button
                    @click="$emit('close')"
                    class="rounded bg-gray-300 px-4 py-2 text-gray-900 dark:bg-gray-700 dark:text-gray-100"
                >
                    Cerrar
                </button>

                <button
                    v-if="mode === 'create'"
                    @click="save"
                    :disabled="loading"
                    class="rounded bg-blue-600 px-4 py-2 text-white"
                >
                    Guardar
                </button>

                <button
                    v-if="mode === 'view' && canEdit"
                    @click="update"
                    :disabled="loading"
                    class="rounded bg-yellow-500 px-4 py-2 text-white"
                >
                    Actualizar
                </button>

                <template v-if="mode === 'view' && isAdmin && absence">
                    <button
                        @click="changeStatus('pendiente')"
                        :disabled="loading"
                        class="rounded bg-slate-600 px-4 py-2 text-white"
                    >
                        Poner pendiente
                    </button>

                    <button
                        @click="changeStatus('rechazado')"
                        :disabled="loading"
                        class="rounded bg-red-600 px-4 py-2 text-white"
                    >
                        Rechazar
                    </button>

                    <button
                        @click="changeStatus('aprobado')"
                        :disabled="loading"
                        class="rounded bg-green-600 px-4 py-2 text-white"
                    >
                        Aprobar
                    </button>

                    <ConfirmDialog
                        title="Eliminar ausencia"
                        description="¿Está seguro de que desea eliminar esta ausencia? Esta acción no se puede deshacer."
                        confirm-text="Eliminar"
                        cancel-text="Cancelar"
                        variant="destructive"
                        @confirm="remove"
                    >
                        <template #trigger>
                            <button
                                :disabled="loading"
                                class="rounded bg-black px-4 py-2 text-white dark:bg-white dark:text-black"
                            >
                                Eliminar
                            </button>
                        </template>
                    </ConfirmDialog>
                </template>
            </div>
        </div>
    </div>
</template>
