<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

interface AbsenceReport {
    id: number;
    empleado: string;
    area: string | null;
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
    area: string | null;
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

const reportType = ref<'personal' | 'area'>('personal');
const selectedYear = ref<number>(new Date().getFullYear());
const startDate = ref(new Date().getFullYear() + '-01-01');
const endDate = ref(new Date().getFullYear() + '-12-31');
const selectedUser = ref('');
const selectedArea = ref('');
const loading = ref(false);
const users = ref<{ id: number; name: string }[]>([]);
const areas = ref<{ id: number; name: string }[]>([]);
const availableYears = ref<number[]>([]);

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

const generateAvailableYears = () => {
    const currentYear = new Date().getFullYear();
    availableYears.value = Array.from({ length: 10 }, (_, i) => currentYear - i).sort(
        (a, b) => b - a
    );
};

const updateDateRange = (year: number) => {
    startDate.value = `${year}-01-01`;
    endDate.value = `${year}-12-31`;
};

const loadReport = async () => {
    if (reportType.value === 'personal' && !selectedUser.value) {
        toast.warning('Por favor selecciona una persona');
        return;
    }
    if (reportType.value === 'area' && !selectedArea.value) {
        toast.warning('Por favor selecciona un área');
        return;
    }

    loading.value = true;
    try {
        const params = new URLSearchParams({
            type: 'absences',
            start: startDate.value,
            end: endDate.value,
        });

        if (reportType.value === 'personal' && selectedUser.value) {
            params.append('user_id', selectedUser.value);
        } else if (reportType.value === 'area' && selectedArea.value) {
            params.append('area_id', selectedArea.value);
        }

        const res = await axios.get(`/reports?${params}`);
        absencesData.value = res.data.data || [];
    } catch (e) {
        toast.error('Error cargando reporte');
        absencesData.value = [];
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
    generateAvailableYears();
    loadUsers();
    loadAreas();
    updateDateRange(selectedYear.value);
    loadReport();
});

watch(selectedYear, (newYear) => {
    updateDateRange(newYear);
    selectedUser.value = '';
    selectedArea.value = '';
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
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Tipo de Reporte -->
                    <div>
                        <label class="label">Tipo de Reporte <span class="text-red-500">*</span></label>
                        <select
                            v-model="reportType"
                            class="input"
                        >
                            <option value="personal">Por Persona</option>
                            <option value="area">Por Área</option>
                        </select>
                    </div>

                    <!-- Año (Obligatorio) -->
                    <div>
                        <label class="label">Año <span class="text-red-500">*</span></label>
                        <select v-model.number="selectedYear" class="input">
                            <option v-for="year in availableYears" :key="year" :value="year">
                                {{ year }}
                            </option>
                        </select>
                    </div>

                    <!-- Empleado (si es Por Persona) -->
                    <div v-if="reportType === 'personal'">
                        <label class="label">Persona <span class="text-red-500">*</span></label>
                        <select
                            v-model="selectedUser"
                            @change="loadReport"
                            class="input"
                        >
                            <option value="">Selecciona una persona...</option>
                            <option
                                v-for="u in users"
                                :key="u.id"
                                :value="u.id"
                            >
                                {{ u.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Área (si es Por Área) -->
                    <div v-if="reportType === 'area'">
                        <label class="label">Área <span class="text-red-500">*</span></label>
                        <select
                            v-model="selectedArea"
                            @change="loadReport"
                            class="input"
                        >
                            <option value="">Selecciona un área...</option>
                            <option
                                v-for="a in areas"
                                :key="a.id"
                                :value="a.id"
                            >
                                {{ a.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Botón Generar Reporte -->
                    <div class="flex items-end">
                        <button
                            @click="loadReport"
                            :disabled="
                                loading ||
                                (reportType === 'personal' && !selectedUser) ||
                                (reportType === 'area' && !selectedArea)
                            "
                            class="btn-primary w-full"
                        >
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
                                    d="M9 12l2 2 4-4M7 12a5 5 0 1110 0A5 5 0 017 12z"
                                />
                            </svg>
                            {{ loading ? 'Generando...' : 'Generar Reporte' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Resumen de Datos -->
            <div
                v-if="absencesData.length > 0"
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
            >
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total de Ausencias</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ absencesData.length }}
                    </p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total de Días</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ (absencesData.reduce((sum, item) => sum + item.dias, 0)).toFixed(1) }}
                    </p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Aprobadas</p>
                    <p class="mt-2 text-2xl font-bold text-green-600 dark:text-green-400">
                        {{ absencesData.filter(a => a.estado === 'aprobado').length }}
                    </p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Rechazadas</p>
                    <p class="mt-2 text-2xl font-bold text-red-600 dark:text-red-400">
                        {{ absencesData.filter(a => a.estado === 'rechazado').length }}
                    </p>
                </div>
            </div>

            <!-- Absences Table -->
            <div
                v-if="!loading && absencesData.length > 0"
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

            <!-- Sin datos -->
            <div
                v-if="!loading && absencesData.length === 0"
                class="rounded-xl border border-gray-200 bg-white p-8 text-center dark:border-gray-700 dark:bg-gray-800"
            >
                <svg
                    class="mx-auto h-12 w-12 text-gray-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    />
                </svg>
                <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-gray-100">
                    No hay datos disponibles
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Selecciona los filtros y genera un reporte para ver los resultados.
                </p>
            </div>

            <!-- Loading -->
            <div
                v-if="loading"
                class="rounded-xl border border-gray-200 bg-white p-8 text-center dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="flex items-center justify-center">
                    <div class="h-8 w-8 animate-spin rounded-full border-4 border-gray-300 border-t-blue-500"></div>
                </div>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Generando reporte...</p>
            </div>

            <!-- Summary - OLD (keeping for reference) -->
            <div v-if="false" class="grid gap-6 lg:grid-cols-2">
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
