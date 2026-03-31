<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import axios from 'axios';
import AbsenceModal from '@/components/AbsenceModal.vue';
import SubscriptionAnnouncementModal from '@/components/SubscriptionAnnouncementModal.vue';

interface Metric {
    total_employees: number;
    pending_absences: number;
    approved_this_month: number;
    total_vacation_days: number;
    used_vacation_days: number;
    upcoming_expirations: number;
}

interface ChartData {
    monthly: { month: string; count: number }[];
    by_type: { name: string; count: number }[];
    by_status: { status: string; count: number }[];
}

interface AbsenceItem {
    id: number;
    user: { id: number; name: string };
    type: { name: string };
    start: string;
    end: string;
    days: number;
    requested_at?: string;
}

interface VacationBalance {
    id: number;
    name: string;
    available: number;
    expiring_soon: { year: number; days: number; expires: string }[];
}

interface User {
    id: number;
    name: string;
    identification: string;
    email: string;
}

const loading = ref(true);
const metrics = ref<Metric | null>(null);
const chartData = ref<ChartData | null>(null);
const recentAbsences = ref<AbsenceItem[]>([]);
const pendingApprovals = ref<AbsenceItem[]>([]);
const vacationBalance = ref<VacationBalance[]>([]);
const users = ref<User[]>([]);
const selectedUser = ref<number | null>(null);
const searchQuery = ref('');

// Modal state
const showAbsenceModal = ref(false);
const absenceModalMode = ref<'create' | 'view'>('create');
const selectedAbsence = ref<any>(null);
const isAdmin = ref(false);
const subscription = ref<any>(null);

// Subscription announcement modal
const showSubscriptionAnnouncement = ref(false);
const subscriptionAnnouncementType = ref<'expiring' | 'payment_required'>(
    'expiring',
);
const subscriptionDaysRemaining = ref(0);
const subscriptionPlanName = ref('');
const subscriptionExpiresAt = ref('');

const loadDashboard = async () => {
    try {
        loading.value = true;
        const params: any = {};
        if (selectedUser.value) {
            params.user_id = selectedUser.value;
        }
        if (searchQuery.value) {
            params.search = searchQuery.value;
        }

        const res = await axios.get('/dashboard/data', { params });
        metrics.value = res.data.metrics;
        chartData.value = res.data.chartData;
        recentAbsences.value = res.data.recentAbsences;
        pendingApprovals.value = res.data.pendingApprovals;
        vacationBalance.value = res.data.vacationBalance;
        if (res.data.users) {
            users.value = res.data.users;
        }
        // Get admin status from API response
        if (res.data.is_admin !== undefined) {
            isAdmin.value = res.data.is_admin;
            subscription.value = res.data.subscription;

            // Show subscription announcement if needed
            if (isAdmin.value && subscription.value) {
                if (
                    subscription.value.show_ad &&
                    subscription.value.days_remaining > 0
                ) {
                    subscriptionAnnouncementType.value = 'expiring';
                    subscriptionDaysRemaining.value =
                        subscription.value.days_remaining;
                    subscriptionPlanName.value =
                        subscription.value.plan_name || '';
                    subscriptionExpiresAt.value =
                        subscription.value.expires_at || '';
                    showSubscriptionAnnouncement.value = true;
                } else if (!subscription.value.has_subscription) {
                    subscriptionAnnouncementType.value = 'payment_required';
                    subscriptionDaysRemaining.value = 0;
                    subscriptionPlanName.value = '';
                    subscriptionExpiresAt.value = '';
                    showSubscriptionAnnouncement.value = true;
                }
            }
        } else {
            // Fallback: check from user data
            const userRes = await axios.get('/me');
            const user = userRes.data;
            isAdmin.value = user?.role === 'admin' || user?.is_admin;
        }
    } catch (e) {
        console.error('Error loading dashboard:', e);
    } finally {
        loading.value = false;
    }
};

watch([selectedUser, searchQuery], () => {
    loadDashboard();
});

onMounted(loadDashboard);

const formatNumber = (n: number) => n.toLocaleString();

const getStatusColor = (status: string) => {
    switch (status) {
        case 'aprobado':
            return 'bg-green-100 text-green-800';
        case 'pendiente':
            return 'bg-amber-100 text-amber-800';
        case 'rechazado':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('es-CO', {
        month: 'short',
        day: 'numeric',
    });

const getStatusLabel = (status: string) => {
    switch (status) {
        case 'aprobado':
            return 'Aprobado';
        case 'pendiente':
            return 'Pendiente';
        case 'rechazado':
            return 'Rechazado';
        default:
            return status;
    }
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4">
            <!-- Header -->
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 dark:text-gray-100"
                    >
                        Dashboard
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Resumen de gestión de ausencias y vacaciones
                    </p>
                </div>
                <!-- Subscription Alert -->
                <div
                    v-if="
                        isAdmin &&
                        subscription &&
                        subscription.has_subscription &&
                        subscription.show_ad
                    "
                    class="rounded-lg bg-amber-50 px-4 py-3 dark:bg-amber-900/30"
                >
                    <div class="flex items-center gap-3">
                        <svg
                            class="h-5 w-5 text-amber-600 dark:text-amber-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                        <div>
                            <p
                                class="text-sm font-medium text-amber-800 dark:text-amber-200"
                            >
                                Tu suscripción está por vencer
                            </p>
                            <p
                                class="text-xs text-amber-700 dark:text-amber-300"
                            >
                                {{ subscription.plan_name }} -
                                {{ subscription.days_remaining }} días restantes
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button @click="loadDashboard" class="btn-secondary">
                        <svg
                            class="mr-2 h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                        Actualizar
                    </button>
                    <button
                        v-if="isAdmin"
                        @click="
                            showAbsenceModal = true;
                            absenceModalMode = 'create';
                            selectedAbsence = null;
                        "
                        class="btn-primary"
                    >
                        <svg
                            class="mr-2 h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        Nueva Ausencia
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                <div class="max-w-md flex-1">
                    <div class="relative">
                        <svg
                            class="absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Buscar por nombre, identificación o correo..."
                            class="w-full rounded-lg border border-gray-300 py-2 pr-4 pl-10 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                        />
                    </div>
                </div>
                <select
                    v-model="selectedUser"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                >
                    <option :value="null">Todos los usuarios</option>
                    <option
                        v-for="user in users"
                        :key="user.id"
                        :value="user.id"
                    >
                        {{ user.name }}
                    </option>
                </select>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="flex justify-center py-12">
                <div
                    class="h-8 w-8 animate-spin rounded-full border-4 border-blue-600 border-t-transparent"
                ></div>
            </div>

            <template v-else>
                <!-- Metrics Cards -->
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div
                        class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p
                                    class="text-sm font-medium text-gray-500 dark:text-gray-400"
                                >
                                    Total Empleados
                                </p>
                                <p
                                    class="text-2xl font-bold text-gray-900 dark:text-gray-100"
                                >
                                    {{ metrics?.total_employees ?? 0 }}
                                </p>
                            </div>
                            <div
                                class="rounded-full bg-blue-100 p-3 dark:bg-blue-900/30"
                            >
                                <svg
                                    class="h-6 w-6 text-blue-600 dark:text-blue-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div
                        class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p
                                    class="text-sm font-medium text-gray-500 dark:text-gray-400"
                                >
                                    Ausencias Pendientes
                                </p>
                                <p
                                    class="text-2xl font-bold text-gray-900 dark:text-gray-100"
                                >
                                    {{ metrics?.pending_absences ?? 0 }}
                                </p>
                            </div>
                            <div
                                class="rounded-full bg-amber-100 p-3 dark:bg-amber-900/30"
                            >
                                <svg
                                    class="h-6 w-6 text-amber-600 dark:text-amber-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div
                        class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p
                                    class="text-sm font-medium text-gray-500 dark:text-gray-400"
                                >
                                    Días de Vacaciones
                                </p>
                                <p
                                    class="text-2xl font-bold text-gray-900 dark:text-gray-100"
                                >
                                    {{
                                        metrics?.total_vacation_days?.toFixed(
                                            1,
                                        ) ?? 0
                                    }}
                                    <span
                                        class="text-sm font-normal text-gray-500"
                                        >/
                                        {{
                                            metrics?.used_vacation_days?.toFixed(
                                                1,
                                            ) ?? 0
                                        }}
                                        usados</span
                                    >
                                </p>
                            </div>
                            <div
                                class="rounded-full bg-emerald-100 p-3 dark:bg-emerald-900/30"
                            >
                                <svg
                                    class="h-6 w-6 text-emerald-600 dark:text-emerald-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div
                        class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p
                                    class="text-sm font-medium text-gray-500 dark:text-gray-400"
                                >
                                    Por Vencer (30 días)
                                </p>
                                <p
                                    class="text-2xl font-bold text-gray-900 dark:text-gray-100"
                                >
                                    {{ metrics?.upcoming_expirations ?? 0 }}
                                </p>
                            </div>
                            <div
                                class="rounded-full bg-red-100 p-3 dark:bg-red-900/30"
                            >
                                <svg
                                    class="h-6 w-6 text-red-600 dark:text-red-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Approvals -->
                <div
                    v-if="pendingApprovals.length > 0"
                    class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20"
                >
                    <div class="mb-4 flex items-center gap-2">
                        <svg
                            class="h-5 w-5 text-amber-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                        <h3
                            class="font-semibold text-amber-900 dark:text-amber-200"
                        >
                            Aprobaciones Pendientes
                        </h3>
                    </div>
                    <div
                        class="grid gap-3 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5"
                    >
                        <div
                            v-for="item in pendingApprovals.slice(0, 10)"
                            :key="item.id"
                            class="rounded-lg bg-white p-3 dark:bg-gray-800"
                        >
                            <div class="flex items-center justify-between">
                                <span
                                    class="font-medium text-gray-900 dark:text-gray-100"
                                    >{{ item.user.name }}</span
                                >
                                <span class="text-sm text-gray-500"
                                    >{{ item.days }} días</span
                                >
                            </div>
                            <div
                                class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                            >
                                {{ item.type.name }} ·
                                {{ formatDate(item.start) }} -
                                {{ formatDate(item.end) }}
                            </div>
                            <div class="mt-2 text-xs text-gray-400">
                                {{ item.requested_at }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts & Tables Grid -->
                <div class="grid gap-6 lg:grid-cols-2">
                    <!-- Vacation Balance -->
                    <div
                        class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <h3
                            class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100"
                        >
                            Balance de Vacaciones
                        </h3>
                        <div class="space-y-3">
                            <div
                                v-if="vacationBalance.length === 0"
                                class="py-4 text-center text-gray-500"
                            >
                                No hay datos de vacaciones
                            </div>
                            <div
                                v-for="user in vacationBalance"
                                :key="user.id"
                                class="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-gray-700/50"
                            >
                                <div>
                                    <p
                                        class="font-medium text-gray-900 dark:text-gray-100"
                                    >
                                        {{ user.name }}
                                    </p>
                                    <div
                                        v-if="user.expiring_soon.length > 0"
                                        class="mt-1 flex items-center gap-2"
                                    >
                                        <span
                                            v-for="exp in user.expiring_soon"
                                            :key="exp.year"
                                            class="inline-flex items-center gap-1 rounded bg-red-100 px-2 py-0.5 text-xs text-red-700 dark:bg-red-900/30 dark:text-red-300"
                                        >
                                            {{ exp.days }}d ({{ exp.year }})
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p
                                        class="text-lg font-bold text-emerald-600 dark:text-emerald-400"
                                    >
                                        {{ user.available.toFixed(1) }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        días disponibles
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Absences by Type -->
                    <div
                        class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <h3
                            class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100"
                        >
                            Ausencias por Tipo
                        </h3>
                        <div class="space-y-3">
                            <div
                                v-if="!chartData?.by_type?.length"
                                class="py-4 text-center text-gray-500"
                            >
                                No hay datos de ausencias
                            </div>
                            <div
                                v-for="item in chartData?.by_type"
                                :key="item.name"
                                class="flex items-center justify-between"
                            >
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-3 w-3 rounded-full bg-blue-500"
                                    ></div>
                                    <span
                                        class="text-gray-700 dark:text-gray-300"
                                        >{{ item.name }}</span
                                    >
                                </div>
                                <span
                                    class="font-medium text-gray-900 dark:text-gray-100"
                                    >{{ item.count }}</span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Absences -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800"
                >
                    <h3
                        class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100"
                    >
                        Ausencias Recientes
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr
                                    class="border-b border-gray-200 text-left text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400"
                                >
                                    <th class="pb-3 font-medium">Empleado</th>
                                    <th class="pb-3 font-medium">Tipo</th>
                                    <th class="pb-3 font-medium">Inicio</th>
                                    <th class="pb-3 font-medium">Fin</th>
                                    <th class="pb-3 font-medium">Días</th>
                                    <th class="pb-3 font-medium">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="recentAbsences.length === 0">
                                    <td
                                        colspan="6"
                                        class="py-4 text-center text-gray-500"
                                    >
                                        No hay ausencias recientes
                                    </td>
                                </tr>
                                <tr
                                    v-for="item in recentAbsences"
                                    :key="item.id"
                                    class="border-b border-gray-100 last:border-0 dark:border-gray-700"
                                >
                                    <td
                                        class="py-3 font-medium text-gray-900 dark:text-gray-100"
                                    >
                                        {{ item.user.name }}
                                    </td>
                                    <td
                                        class="py-3 text-gray-600 dark:text-gray-400"
                                    >
                                        {{ item.type.name }}
                                    </td>
                                    <td
                                        class="py-3 text-gray-600 dark:text-gray-400"
                                    >
                                        {{ formatDate(item.start) }}
                                    </td>
                                    <td
                                        class="py-3 text-gray-600 dark:text-gray-400"
                                    >
                                        {{ formatDate(item.end) }}
                                    </td>
                                    <td
                                        class="py-3 text-gray-600 dark:text-gray-400"
                                    >
                                        {{ item.days }}
                                    </td>
                                    <td class="py-3">
                                        <span
                                            class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800"
                                        >
                                            Aprobado
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </div>

        <!-- Absence Modal -->
        <AbsenceModal
            :show="showAbsenceModal"
            :mode="absenceModalMode"
            :absence="selectedAbsence"
            :users="users"
            :isAdmin="isAdmin"
            @close="showAbsenceModal = false"
            @saved="loadDashboard"
        />

        <!-- Subscription Announcement Modal -->
        <SubscriptionAnnouncementModal
            :show="showSubscriptionAnnouncement"
            :type="subscriptionAnnouncementType"
            :days-remaining="subscriptionDaysRemaining"
            :plan-name="subscriptionPlanName"
            :expires-at="subscriptionExpiresAt"
            @close="showSubscriptionAnnouncement = false"
            @renew="
                () => {
                    showSubscriptionAnnouncement.value = false;
                }
            "
        />
    </AppLayout>
</template>

<style scoped>
.btn-primary {
    display: inline-flex;
    align-items: center;
    border: none;
    border-radius: 0.25rem;
    background-color: #2563eb;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: white;
    cursor: pointer;
}

.btn-primary:hover {
    background-color: #1d4ed8;
}

.btn-secondary {
    display: inline-flex;
    align-items: center;
    border: 1px solid #d1d5db;
    border-radius: 0.25rem;
    background-color: white;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
}
</style>
