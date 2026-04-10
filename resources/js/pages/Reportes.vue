<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

interface AbsenceReport {
    id: number;
    empleado: string;
    tipo: string;
    inicio: string;
    fin: string;
    dias: number;
    horas: number;
    estado: string;
    aprobado_por: string | null;
    aprobado_en: string | null;
}

interface VacationReport {
    id: number;
    empleado: string;
    año: number;
    asignados: number;
    usados: number;
    disponibles: number;
    vencimiento: string;
    vencido: boolean;
}

interface SummaryData {
    filters: Record<string, any>;
    absences: {
        total: number;
        total_days: number;
        by_status: Record<string, number>;
        by_type: Record<string, { count: number; days: number }>;
    };
    employees: {
        total: number;
        vacation_summary: {
            name: string;
            area: string | null;
            allocated: number;
            used: number;
            available: number;
        }[];
    };
}

const toast = useToast();

const reportType = ref<'absences' | 'vacations' | 'summary'>('absences');
const startDate = ref(new Date().getFullYear() + '-01-01');
const endDate = ref(new Date().getFullYear() + '-12-31');
const selectedUser = ref('');
const selectedArea = ref('');
const loading = ref(false);
const users = ref<{ id: number; name: string }[]>([]);
const areas = ref<{ id: number; name: string }[]>([]);

const absencesData = ref<AbsenceReport[]>([]);
const vacationsData = ref<VacationReport[]>([]);
const summaryData = ref<SummaryData | null>(null);

const loadUsers = async () => {
    try {
        const res = await axios.get('/users-list');
        users.value = res.data.map((u: any) => ({ id: u.id, name: u.name }));
    } catch (e) {
        console.error(e);
    }
};

const loadAreas = async () => {
    try {
        const res = await axios.get('/areas-list');
        areas.value = res.data;
    } catch (e) {
        console.error(e);
    }
};

const loadReport = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams({
            type: reportType.value,
            start: startDate.value,
            end: endDate.value,
        });
        if (selectedUser.value) {
            params.append('user_id', selectedUser.value);
        }
        if (selectedArea.value) {
            params.append('area_id', selectedArea.value);
        }

        const res = await axios.get(`/reports?${params}`);

        if (reportType.value === 'absences') {
            absencesData.value = res.data.data;
        } else if (reportType.value === 'vacations') {
            vacationsData.value = res.data.data;
        } else {
            summaryData.value = res.data;
        }
    } catch (e) {
        toast.error('Error cargando reporte');
    } finally {
        loading.value = false;
    }
};

const exportReport = async () => {
    try {
        const params = new URLSearchParams({
            type: reportType.value,
            start: startDate.value,
            end: endDate.value,
            format: 'csv',
        });
        if (selectedUser.value) {
            params.append('user_id', selectedUser.value);
        }
        if (selectedArea.value) {
            params.append('area_id', selectedArea.value);
        }

        const response = await axios.get(`/reports/export?${params}`, {
            responseType: 'blob',
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        const filename = selectedArea.value
            ? `reporte-${reportType.value}-${startDate.value}-${endDate.value}-area-${selectedArea.value}.csv`
            : `reporte-${reportType.value}-${startDate.value}-${endDate.value}.csv`;
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);

        toast.success('Reporte exportado');
    } catch (e) {
        toast.error('Error exportando reporte');
    }
};

onMounted(() => {
    loadUsers();
    loadAreas();
    loadReport();
});

const formatDate = (date: string) => new Date(date).toLocaleDateString('es-CO');

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'aprobado':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'pendiente':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
        case 'rechazado':
            return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4">
            <!-- Header -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 dark:text-gray-100"
                    >
                        Reportes
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Genera informes de ausencias y vacaciones
                    </p>
                </div>
                <button @click="exportReport" class="btn-primary">
                    <svg
                        class="mr-2 h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                    Exportar CSV
                </button>
            </div>

            <!-- Filters -->
            <div
                class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="flex flex-wrap gap-4">
                    <div>
                        <label class="label">Tipo de Reporte</label>
                        <select
                            v-model="reportType"
                            @change="loadReport"
                            class="input"
                        >
                            <option value="absences">Ausencias</option>
                            <option value="vacations">Vacaciones</option>
                            <option value="summary">Resumen General</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Fecha Inicio</label>
                        <input
                            v-model="startDate"
                            type="date"
                            class="input"
                            @change="loadReport"
                        />
                    </div>
                    <div>
                        <label class="label">Fecha Fin</label>
                        <input
                            v-model="endDate"
                            type="date"
                            class="input"
                            @change="loadReport"
                        />
                    </div>
                    <div>
                        <label class="label">Empleado</label>
                        <select
                            v-model="selectedUser"
                            @change="loadReport"
                            class="input"
                        >
                            <option value="">Todos</option>
                            <option
                                v-for="u in users"
                                :key="u.id"
                                :value="u.id"
                            >
                                {{ u.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Área</label>
                        <select
                            v-model="selectedArea"
                            @change="loadReport"
                            class="input"
                        >
                            <option value="">Todas</option>
                            <option
                                v-for="a in areas"
                                :key="a.id"
                                :value="a.id"
                            >
                                {{ a.name }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Absences Table -->
            <div
                v-if="reportType === 'absences'"
                class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                                >
                                    Empleado
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                                >
                                    Área
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                                >
                                    Tipo
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                                >
                                    Inicio
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                                >
                                    Fin
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                                >
                                    Días
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                                >
                                    Horas
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                                >
                                    Estado
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-700"
                        >
                            <tr v-if="loading" class="text-center">
                                <td colspan="8" class="py-8 text-gray-500">
                                    Cargando...
                                </td>
                            </tr>
                            <tr
                                v-else-if="absencesData.length === 0"
                                class="text-center"
                            >
                                <td colspan="8" class="py-8 text-gray-500">
                                    No hay datos
                                </td>
                            </tr>
                            <tr
                                v-for="item in absencesData"
                                :key="item.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/30"
                            >
                                <td
                                    class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100"
                                >
                                    {{ item.empleado }}
                                </td>
                                <td
                                    class="px-4 py-3 text-gray-600 dark:text-gray-400"
                                >
                                    {{ item.area || '-' }}
                                </td>
                                <td
                                    class="px-4 py-3 text-gray-600 dark:text-gray-400"
                                >
                                    {{ item.tipo }}
                                </td>
                                <td
                                    class="px-4 py-3 text-gray-600 dark:text-gray-400"
                                >
                                    {{ formatDate(item.inicio) }}
                                </td>
                                <td
                                    class="px-4 py-3 text-gray-600 dark:text-gray-400"
                                >
                                    {{ formatDate(item.fin) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    {{ item.dias }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    {{ item.horas }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="[
                                            'rounded-full px-2.5 py-0.5 text-xs font-medium capitalize',
                                            getStatusBadge(item.estado),
                                        ]"
                                    >
                                        {{ item.estado }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Vacations Table -->
            <div
                v-if="reportType === 'vacations'"
                class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                                >
                                    Empleado
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                                >
                                    Área
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                                >
                                    Año
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                                >
                                    Asignados
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                                >
                                    Usados
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                                >
                                    Disponibles
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                                >
                                    Vencimiento
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                                >
                                    Estado
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-700"
                        >
                            <tr v-if="loading" class="text-center">
                                <td colspan="8" class="py-8 text-gray-500">
                                    Cargando...
                                </td>
                            </tr>
                            <tr
                                v-else-if="vacationsData.length === 0"
                                class="text-center"
                            >
                                <td colspan="8" class="py-8 text-gray-500">
                                    No hay datos
                                </td>
                            </tr>
                            <tr
                                v-for="item in vacationsData"
                                :key="item.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/30"
                            >
                                <td
                                    class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100"
                                >
                                    {{ item.empleado }}
                                </td>
                                <td
                                    class="px-4 py-3 text-gray-600 dark:text-gray-400"
                                >
                                    {{ item.area || '-' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    {{ item.año }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    {{ item.asignados }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    {{ item.usados }}
                                </td>
                                <td
                                    class="px-4 py-3 text-center font-bold text-emerald-600 dark:text-emerald-400"
                                >
                                    {{ item.disponibles }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    {{ formatDate(item.vencimiento) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="[
                                            'rounded-full px-2.5 py-0.5 text-xs font-medium',
                                            item.vencido
                                                ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
                                                : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                        ]"
                                    >
                                        {{
                                            item.vencido ? 'Vencido' : 'Activo'
                                        }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Summary -->
            <div
                v-if="reportType === 'summary' && summaryData"
                class="grid gap-6 lg:grid-cols-2"
            >
                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800"
                >
                    <h3
                        class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100"
                    >
                        Resumen de Ausencias
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400"
                                >Total Ausencias</span
                            >
                            <span
                                class="font-bold text-gray-900 dark:text-gray-100"
                                >{{ summaryData.absences.total }}</span
                            >
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400"
                                >Total Días</span
                            >
                            <span
                                class="font-bold text-gray-900 dark:text-gray-100"
                                >{{
                                    summaryData.absences.total_days.toFixed(1)
                                }}</span
                            >
                        </div>
                        <div class="border-t pt-3">
                            <p
                                class="mb-2 text-sm font-medium text-gray-500 dark:text-gray-400"
                            >
                                Por Estado
                            </p>
                            <div
                                v-for="(count, status) in summaryData.absences
                                    .by_status"
                                :key="status"
                                class="flex justify-between text-sm"
                            >
                                <span
                                    class="text-gray-600 capitalize dark:text-gray-400"
                                    >{{ status }}</span
                                >
                                <span
                                    class="font-medium text-gray-900 dark:text-gray-100"
                                    >{{ count }}</span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800"
                >
                    <h3
                        class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100"
                    >
                        Resumen de Vacaciones
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400"
                                >Total Empleados</span
                            >
                            <span
                                class="font-bold text-gray-900 dark:text-gray-100"
                                >{{ summaryData.employees.total }}</span
                            >
                        </div>
                        <div class="border-t pt-3">
                            <p
                                class="mb-2 text-sm font-medium text-gray-500 dark:text-gray-400"
                            >
                                Por Empleado
                            </p>
                            <div class="max-h-64 space-y-2 overflow-y-auto">
                                <div
                                    v-for="emp in summaryData.employees
                                        .vacation_summary"
                                    :key="emp.name"
                                    class="flex justify-between text-sm"
                                >
                                    <span
                                        class="text-gray-600 dark:text-gray-400"
                                        >{{ emp.name }}
                                        <span
                                            v-if="emp.area"
                                            class="text-xs text-gray-400"
                                            >({{ emp.area }})</span
                                        >
                                    </span>
                                    <span
                                        class="font-medium text-emerald-600 dark:text-emerald-400"
                                        >{{
                                            emp.available.toFixed(1)
                                        }}
                                        días</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
