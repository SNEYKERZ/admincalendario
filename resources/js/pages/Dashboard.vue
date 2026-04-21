<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import axios from 'axios';
import AbsenceModal from '@/components/AbsenceModal.vue';
import SubscriptionAnnouncementModal from '@/components/SubscriptionAnnouncementModal.vue';

type Role = 'colaborador' | 'admin' | 'superadmin';

interface Viewer {
    id: number;
    name: string;
    role: Role;
    is_admin: boolean;
    is_superadmin: boolean;
}

interface MetricPersonal {
    available_vacation_days: number;
    used_vacation_days: number;
    pending_absences: number;
    approved_this_month: number;
    upcoming_expirations: number;
}

interface MetricOrganization {
    total_employees: number;
    pending_absences: number;
    approved_this_month: number;
    total_vacation_days: number;
    upcoming_expirations: number;
    unavailable_now: number;
}

interface MetricSuperadmin {
    total_admins: number;
    total_superadmins: number;
    active_tenants: number;
    active_subscriptions: number;
    subscriptions_expiring_30d: number;
}

interface UserItem {
    id: number;
    name: string;
    identification: string;
    email: string;
}

interface AreaItem {
    id: number;
    name: string;
    color: string;
}

interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface EmployeeStatusItem {
    id: number;
    name: string;
    status: 'green' | 'yellow' | 'red';
    current_absence: { type: string; end: string } | null;
    upcoming_absence: { type: string; start: string } | null;
}

interface AbsenceItem {
    id: number;
    user: { id: number; name: string };
    type: { name: string | null };
    start: string;
    end: string;
    days: number;
    requested_at?: string;
}

interface VacationBalanceItem {
    id: number;
    name: string;
    available: number;
    expiring_soon: { year: number; days: number; expires: string }[];
}

interface ExpiringVacationItem {
    id: number;
    year: number;
    available_days: number;
    expires_at: string;
}

interface DashboardResponse {
    viewer: Viewer;
    metrics: {
        personal: MetricPersonal;
        organization: MetricOrganization | null;
        superadmin: MetricSuperadmin | null;
    };
    filters: {
        selected_user_id: number | null;
        selected_area_id: number | null;
        users: UserItem[];
        areas: AreaItem[];
    };
    tables: {
        employee_statuses: {
            data: EmployeeStatusItem[];
            meta: PaginationMeta;
            summary: { green: number; yellow: number; red: number; total: number };
            search: string;
        };
        recent_absences: {
            data: AbsenceItem[];
            meta: PaginationMeta;
            search: string;
        };
        pending_approvals: {
            data: AbsenceItem[];
            meta: PaginationMeta;
            search: string;
        };
        vacation_balances: {
            data: VacationBalanceItem[];
            meta: PaginationMeta;
            search: string;
        } | null;
        my_expiring_vacations: ExpiringVacationItem[] | null;
    };
    subscription: {
        has_subscription: boolean;
        show_ad: boolean;
        plan_name?: string;
        days_remaining?: number;
        expires_at?: string;
    } | null;
}

type TableKey = 'status' | 'recent' | 'pending' | 'vacation';
interface TableState {
    search: string;
    page: number;
    perPage: number;
}

const loading = ref(true);
const viewer = ref<Viewer | null>(null);
const personalMetrics = ref<MetricPersonal | null>(null);
const organizationMetrics = ref<MetricOrganization | null>(null);
const superadminMetrics = ref<MetricSuperadmin | null>(null);
const users = ref<UserItem[]>([]);
const areas = ref<AreaItem[]>([]);
const selectedUser = ref<number | null>(null);
const selectedArea = ref<number | null>(null);
const subscription = ref<DashboardResponse['subscription']>(null);

const employeeStatuses = ref<EmployeeStatusItem[]>([]);
const employeeSummary = ref({ green: 0, yellow: 0, red: 0, total: 0 });
const employeeMeta = ref<PaginationMeta>({
    current_page: 1,
    last_page: 1,
    per_page: 5,
    total: 0,
});

const recentAbsences = ref<AbsenceItem[]>([]);
const recentMeta = ref<PaginationMeta>({
    current_page: 1,
    last_page: 1,
    per_page: 5,
    total: 0,
});

const pendingApprovals = ref<AbsenceItem[]>([]);
const pendingMeta = ref<PaginationMeta>({
    current_page: 1,
    last_page: 1,
    per_page: 5,
    total: 0,
});

const vacationBalances = ref<VacationBalanceItem[]>([]);
const vacationMeta = ref<PaginationMeta>({
    current_page: 1,
    last_page: 1,
    per_page: 5,
    total: 0,
});

const myExpiringVacations = ref<ExpiringVacationItem[]>([]);

const tableStates = reactive<Record<TableKey, TableState>>({
    status: { search: '', page: 1, perPage: 5 },
    recent: { search: '', page: 1, perPage: 5 },
    pending: { search: '', page: 1, perPage: 5 },
    vacation: { search: '', page: 1, perPage: 5 },
});

const perPageOptions = [5, 10];

const isAdmin = computed(() => viewer.value?.is_admin ?? false);
const isSuperAdmin = computed(() => viewer.value?.is_superadmin ?? false);

const showAbsenceModal = ref(false);
const absenceModalMode = ref<'create' | 'view'>('create');
const selectedAbsence = ref<any>(null);

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
        const params: Record<string, string | number> = {
            status_page: tableStates.status.page,
            status_per_page: tableStates.status.perPage,
            status_search: tableStates.status.search,
            recent_page: tableStates.recent.page,
            recent_per_page: tableStates.recent.perPage,
            recent_search: tableStates.recent.search,
            pending_page: tableStates.pending.page,
            pending_per_page: tableStates.pending.perPage,
            pending_search: tableStates.pending.search,
            vacation_page: tableStates.vacation.page,
            vacation_per_page: tableStates.vacation.perPage,
            vacation_search: tableStates.vacation.search,
        };

        if (selectedUser.value) {
            params.user_id = selectedUser.value;
        }

        if (selectedArea.value) {
            params.area_id = selectedArea.value;
        }

        const response = await axios.get<DashboardResponse>('/dashboard/data', {
            params,
        });

        const data = response.data;
        viewer.value = data.viewer;
        personalMetrics.value = data.metrics.personal;
        organizationMetrics.value = data.metrics.organization;
        superadminMetrics.value = data.metrics.superadmin;
        users.value = data.filters.users;
        areas.value = data.filters.areas;
        subscription.value = data.subscription;

        employeeStatuses.value = data.tables.employee_statuses.data;
        employeeMeta.value = data.tables.employee_statuses.meta;
        employeeSummary.value = data.tables.employee_statuses.summary;

        recentAbsences.value = data.tables.recent_absences.data;
        recentMeta.value = data.tables.recent_absences.meta;

        pendingApprovals.value = data.tables.pending_approvals.data;
        pendingMeta.value = data.tables.pending_approvals.meta;

        vacationBalances.value = data.tables.vacation_balances?.data ?? [];
        vacationMeta.value = data.tables.vacation_balances?.meta ?? {
            current_page: 1,
            last_page: 1,
            per_page: 5,
            total: 0,
        };

        myExpiringVacations.value = data.tables.my_expiring_vacations ?? [];

        if (subscription.value && isAdmin.value && !isSuperAdmin.value) {
            if (
                subscription.value.show_ad &&
                (subscription.value.days_remaining ?? 0) > 0
            ) {
                subscriptionAnnouncementType.value = 'expiring';
                subscriptionDaysRemaining.value =
                    subscription.value.days_remaining ?? 0;
                subscriptionPlanName.value = subscription.value.plan_name ?? '';
                subscriptionExpiresAt.value = subscription.value.expires_at ?? '';
                showSubscriptionAnnouncement.value = true;
            } else if (!subscription.value.has_subscription) {
                subscriptionAnnouncementType.value = 'payment_required';
                subscriptionDaysRemaining.value = 0;
                subscriptionPlanName.value = '';
                subscriptionExpiresAt.value = '';
                showSubscriptionAnnouncement.value = true;
            } else {
                showSubscriptionAnnouncement.value = false;
            }
        }
    } catch (error) {
        console.error('Error loading dashboard:', error);
    } finally {
        loading.value = false;
    }
};

const applyGlobalFilters = () => {
    tableStates.status.page = 1;
    tableStates.recent.page = 1;
    tableStates.pending.page = 1;
    tableStates.vacation.page = 1;
    loadDashboard();
};

const applySearch = (table: TableKey) => {
    tableStates[table].page = 1;
    loadDashboard();
};

const clearSearch = (table: TableKey) => {
    tableStates[table].search = '';
    tableStates[table].page = 1;
    loadDashboard();
};

const setPage = (table: TableKey, page: number) => {
    if (page < 1) return;
    tableStates[table].page = page;
    loadDashboard();
};

const setPerPage = (table: TableKey, perPage: number) => {
    tableStates[table].perPage = perPage;
    tableStates[table].page = 1;
    loadDashboard();
};

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('es-CO', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });

onMounted(loadDashboard);
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        Dashboard
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{
                            isAdmin
                                ? 'Vista organizacional con control por áreas, ausencias y vacaciones.'
                                : 'Vista personal de ausencias, vacaciones y disponibilidad del equipo.'
                        }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <button @click="loadDashboard" class="btn-secondary">
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
                        Nueva Ausencia
                    </button>
                </div>
            </div>

            <div
                v-if="
                    isAdmin &&
                    subscription &&
                    subscription.has_subscription &&
                    subscription.show_ad
                "
                class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900"
            >
                Plan {{ subscription.plan_name }}: vence en
                {{ subscription.days_remaining }} días.
            </div>

            <div
                v-if="isAdmin"
                class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="grid gap-3 lg:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Colaborador
                        </label>
                        <select
                            v-model="selectedUser"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                        >
                            <option :value="null">Todos</option>
                            <option v-for="user in users" :key="user.id" :value="user.id">
                                {{ user.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Área
                        </label>
                        <select
                            v-model="selectedArea"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                        >
                            <option :value="null">Todas</option>
                            <option v-for="area in areas" :key="area.id" :value="area.id">
                                {{ area.name }}
                            </option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button class="btn-secondary w-full" @click="applyGlobalFilters">
                            Aplicar filtros
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="loading" class="flex justify-center py-12">
                <div class="h-8 w-8 animate-spin rounded-full border-4 border-blue-600 border-t-transparent"></div>
            </div>

            <template v-else>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-sm text-gray-500">Mis días disponibles</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ personalMetrics?.available_vacation_days ?? 0 }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-sm text-gray-500">Mis días usados (año)</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ personalMetrics?.used_vacation_days ?? 0 }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-sm text-gray-500">Mis pendientes</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ personalMetrics?.pending_absences ?? 0 }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-sm text-gray-500">Mis aprobadas este mes</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ personalMetrics?.approved_this_month ?? 0 }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-sm text-gray-500">Vencen en 30 días</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ personalMetrics?.upcoming_expirations ?? 0 }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="isAdmin && organizationMetrics"
                    class="grid gap-4 md:grid-cols-2 lg:grid-cols-3"
                >
                    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                        <p class="text-sm text-blue-700">Total empleados</p>
                        <p class="text-2xl font-bold text-blue-900">
                            {{ organizationMetrics.total_employees }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <p class="text-sm text-amber-700">Pendientes (equipo)</p>
                        <p class="text-2xl font-bold text-amber-900">
                            {{ organizationMetrics.pending_absences }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
                        <p class="text-sm text-rose-700">No disponibles ahora</p>
                        <p class="text-2xl font-bold text-rose-900">
                            {{ organizationMetrics.unavailable_now }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="isSuperAdmin && superadminMetrics"
                    class="grid gap-4 md:grid-cols-2 lg:grid-cols-5"
                >
                    <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                        <p class="text-sm text-indigo-700">Admins activos</p>
                        <p class="text-2xl font-bold text-indigo-900">
                            {{ superadminMetrics.total_admins }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                        <p class="text-sm text-indigo-700">Superadmins</p>
                        <p class="text-2xl font-bold text-indigo-900">
                            {{ superadminMetrics.total_superadmins }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                        <p class="text-sm text-indigo-700">Tenants activos</p>
                        <p class="text-2xl font-bold text-indigo-900">
                            {{ superadminMetrics.active_tenants }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                        <p class="text-sm text-indigo-700">Suscripciones activas</p>
                        <p class="text-2xl font-bold text-indigo-900">
                            {{ superadminMetrics.active_subscriptions }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                        <p class="text-sm text-indigo-700">Vencen en 30 días</p>
                        <p class="text-2xl font-bold text-indigo-900">
                            {{ superadminMetrics.subscriptions_expiring_30d }}
                        </p>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
                    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                            Disponibilidad de colaboradores
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            <input
                                v-model="tableStates.status.search"
                                class="rounded-md border border-gray-300 px-3 py-2 text-sm"
                                placeholder="Buscar colaborador..."
                                @keyup.enter="applySearch('status')"
                            />
                            <select
                                :value="tableStates.status.perPage"
                                class="rounded-md border border-gray-300 px-3 py-2 text-sm"
                                @change="setPerPage('status', Number(($event.target as HTMLSelectElement).value))"
                            >
                                <option v-for="option in perPageOptions" :key="option" :value="option">
                                    {{ option }}
                                </option>
                            </select>
                            <button class="btn-secondary" @click="applySearch('status')">
                                Buscar
                            </button>
                            <button class="btn-secondary" @click="clearSearch('status')">
                                Limpiar
                            </button>
                        </div>
                    </div>
                    <div class="mb-4 grid gap-2 md:grid-cols-4">
                        <div class="rounded-md bg-emerald-50 p-3 text-sm text-emerald-800">
                            Disponibles: {{ employeeSummary.green }}
                        </div>
                        <div class="rounded-md bg-amber-50 p-3 text-sm text-amber-800">
                            Próximos: {{ employeeSummary.yellow }}
                        </div>
                        <div class="rounded-md bg-rose-50 p-3 text-sm text-rose-800">
                            Ausentes: {{ employeeSummary.red }}
                        </div>
                        <div class="rounded-md bg-slate-100 p-3 text-sm text-slate-800">
                            Total: {{ employeeSummary.total }}
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 text-left text-slate-600">
                                    <th class="px-2 py-2">Colaborador</th>
                                    <th class="px-2 py-2">Estado</th>
                                    <th class="px-2 py-2">Ausencia actual</th>
                                    <th class="px-2 py-2">Próxima ausencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="employee in employeeStatuses"
                                    :key="employee.id"
                                    class="border-b border-slate-100"
                                >
                                    <td class="px-2 py-2 font-medium text-slate-900 dark:text-slate-100">
                                        {{ employee.name }}
                                    </td>
                                    <td class="px-2 py-2">
                                        <span
                                            class="rounded-full px-2 py-1 text-xs font-medium"
                                            :class="{
                                                'bg-emerald-100 text-emerald-800': employee.status === 'green',
                                                'bg-amber-100 text-amber-800': employee.status === 'yellow',
                                                'bg-rose-100 text-rose-800': employee.status === 'red',
                                            }"
                                        >
                                            {{
                                                employee.status === 'green'
                                                    ? 'Disponible'
                                                    : employee.status === 'yellow'
                                                      ? 'Próximo'
                                                      : 'Ausente'
                                            }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-2 text-slate-600">
                                        <span v-if="employee.current_absence">
                                            {{ employee.current_absence.type }}
                                            hasta {{ formatDate(employee.current_absence.end) }}
                                        </span>
                                        <span v-else>-</span>
                                    </td>
                                    <td class="px-2 py-2 text-slate-600">
                                        <span v-if="employee.upcoming_absence">
                                            {{ employee.upcoming_absence.type }}
                                            desde {{ formatDate(employee.upcoming_absence.start) }}
                                        </span>
                                        <span v-else>-</span>
                                    </td>
                                </tr>
                                <tr v-if="employeeStatuses.length === 0">
                                    <td colspan="4" class="px-2 py-6 text-center text-slate-500">
                                        Sin resultados
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-sm text-slate-600">
                        <span>
                            Página {{ employeeMeta.current_page }} de {{ employeeMeta.last_page }} ·
                            {{ employeeMeta.total }} registros
                        </span>
                        <div class="flex gap-2">
                            <button
                                class="btn-secondary"
                                :disabled="employeeMeta.current_page <= 1"
                                @click="setPage('status', employeeMeta.current_page - 1)"
                            >
                                Anterior
                            </button>
                            <button
                                class="btn-secondary"
                                :disabled="employeeMeta.current_page >= employeeMeta.last_page"
                                @click="setPage('status', employeeMeta.current_page + 1)"
                            >
                                Siguiente
                            </button>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ isAdmin ? 'Aprobaciones pendientes' : 'Mis solicitudes pendientes' }}
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            <input
                                v-model="tableStates.pending.search"
                                class="rounded-md border border-gray-300 px-3 py-2 text-sm"
                                placeholder="Buscar pendiente..."
                                @keyup.enter="applySearch('pending')"
                            />
                            <select
                                :value="tableStates.pending.perPage"
                                class="rounded-md border border-gray-300 px-3 py-2 text-sm"
                                @change="setPerPage('pending', Number(($event.target as HTMLSelectElement).value))"
                            >
                                <option v-for="option in perPageOptions" :key="option" :value="option">
                                    {{ option }}
                                </option>
                            </select>
                            <button class="btn-secondary" @click="applySearch('pending')">Buscar</button>
                            <button class="btn-secondary" @click="clearSearch('pending')">Limpiar</button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-left text-gray-500">
                                    <th class="px-2 py-2">Empleado</th>
                                    <th class="px-2 py-2">Tipo</th>
                                    <th class="px-2 py-2">Inicio</th>
                                    <th class="px-2 py-2">Fin</th>
                                    <th class="px-2 py-2">Días</th>
                                    <th class="px-2 py-2">Solicitado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="absence in pendingApprovals" :key="absence.id" class="border-b border-gray-100">
                                    <td class="px-2 py-2 font-medium text-gray-900 dark:text-gray-100">
                                        {{ absence.user.name }}
                                    </td>
                                    <td class="px-2 py-2">{{ absence.type.name || '-' }}</td>
                                    <td class="px-2 py-2">{{ formatDate(absence.start) }}</td>
                                    <td class="px-2 py-2">{{ formatDate(absence.end) }}</td>
                                    <td class="px-2 py-2">{{ absence.days }}</td>
                                    <td class="px-2 py-2">{{ absence.requested_at }}</td>
                                </tr>
                                <tr v-if="pendingApprovals.length === 0">
                                    <td colspan="6" class="px-2 py-6 text-center text-gray-500">
                                        Sin pendientes
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-sm text-gray-600">
                        <span>
                            Página {{ pendingMeta.current_page }} de {{ pendingMeta.last_page }} ·
                            {{ pendingMeta.total }} registros
                        </span>
                        <div class="flex gap-2">
                            <button
                                class="btn-secondary"
                                :disabled="pendingMeta.current_page <= 1"
                                @click="setPage('pending', pendingMeta.current_page - 1)"
                            >
                                Anterior
                            </button>
                            <button
                                class="btn-secondary"
                                :disabled="pendingMeta.current_page >= pendingMeta.last_page"
                                @click="setPage('pending', pendingMeta.current_page + 1)"
                            >
                                Siguiente
                            </button>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ isAdmin ? 'Ausencias recientes del equipo' : 'Mis ausencias recientes' }}
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            <input
                                v-model="tableStates.recent.search"
                                class="rounded-md border border-gray-300 px-3 py-2 text-sm"
                                placeholder="Buscar ausencia..."
                                @keyup.enter="applySearch('recent')"
                            />
                            <select
                                :value="tableStates.recent.perPage"
                                class="rounded-md border border-gray-300 px-3 py-2 text-sm"
                                @change="setPerPage('recent', Number(($event.target as HTMLSelectElement).value))"
                            >
                                <option v-for="option in perPageOptions" :key="option" :value="option">
                                    {{ option }}
                                </option>
                            </select>
                            <button class="btn-secondary" @click="applySearch('recent')">Buscar</button>
                            <button class="btn-secondary" @click="clearSearch('recent')">Limpiar</button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-left text-gray-500">
                                    <th class="px-2 py-2">Empleado</th>
                                    <th class="px-2 py-2">Tipo</th>
                                    <th class="px-2 py-2">Inicio</th>
                                    <th class="px-2 py-2">Fin</th>
                                    <th class="px-2 py-2">Días</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="absence in recentAbsences" :key="absence.id" class="border-b border-gray-100">
                                    <td class="px-2 py-2 font-medium text-gray-900 dark:text-gray-100">
                                        {{ absence.user.name }}
                                    </td>
                                    <td class="px-2 py-2">{{ absence.type.name || '-' }}</td>
                                    <td class="px-2 py-2">{{ formatDate(absence.start) }}</td>
                                    <td class="px-2 py-2">{{ formatDate(absence.end) }}</td>
                                    <td class="px-2 py-2">{{ absence.days }}</td>
                                </tr>
                                <tr v-if="recentAbsences.length === 0">
                                    <td colspan="5" class="px-2 py-6 text-center text-gray-500">
                                        Sin resultados
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-sm text-gray-600">
                        <span>
                            Página {{ recentMeta.current_page }} de {{ recentMeta.last_page }} ·
                            {{ recentMeta.total }} registros
                        </span>
                        <div class="flex gap-2">
                            <button
                                class="btn-secondary"
                                :disabled="recentMeta.current_page <= 1"
                                @click="setPage('recent', recentMeta.current_page - 1)"
                            >
                                Anterior
                            </button>
                            <button
                                class="btn-secondary"
                                :disabled="recentMeta.current_page >= recentMeta.last_page"
                                @click="setPage('recent', recentMeta.current_page + 1)"
                            >
                                Siguiente
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    v-if="isAdmin"
                    class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800"
                >
                    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Balance de vacaciones por colaborador
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            <input
                                v-model="tableStates.vacation.search"
                                class="rounded-md border border-gray-300 px-3 py-2 text-sm"
                                placeholder="Buscar colaborador..."
                                @keyup.enter="applySearch('vacation')"
                            />
                            <select
                                :value="tableStates.vacation.perPage"
                                class="rounded-md border border-gray-300 px-3 py-2 text-sm"
                                @change="setPerPage('vacation', Number(($event.target as HTMLSelectElement).value))"
                            >
                                <option v-for="option in perPageOptions" :key="option" :value="option">
                                    {{ option }}
                                </option>
                            </select>
                            <button class="btn-secondary" @click="applySearch('vacation')">Buscar</button>
                            <button class="btn-secondary" @click="clearSearch('vacation')">Limpiar</button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-left text-gray-500">
                                    <th class="px-2 py-2">Colaborador</th>
                                    <th class="px-2 py-2">Días disponibles</th>
                                    <th class="px-2 py-2">Próximos vencimientos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in vacationBalances" :key="item.id" class="border-b border-gray-100">
                                    <td class="px-2 py-2 font-medium text-gray-900 dark:text-gray-100">
                                        {{ item.name }}
                                    </td>
                                    <td class="px-2 py-2">{{ item.available }}</td>
                                    <td class="px-2 py-2">
                                        <span v-if="item.expiring_soon.length">
                                            {{
                                                item.expiring_soon
                                                    .map((row) => `${row.days} días (${formatDate(row.expires)})`)
                                                    .join(' · ')
                                            }}
                                        </span>
                                        <span v-else>-</span>
                                    </td>
                                </tr>
                                <tr v-if="vacationBalances.length === 0">
                                    <td colspan="3" class="px-2 py-6 text-center text-gray-500">
                                        Sin resultados
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-sm text-gray-600">
                        <span>
                            Página {{ vacationMeta.current_page }} de {{ vacationMeta.last_page }} ·
                            {{ vacationMeta.total }} registros
                        </span>
                        <div class="flex gap-2">
                            <button
                                class="btn-secondary"
                                :disabled="vacationMeta.current_page <= 1"
                                @click="setPage('vacation', vacationMeta.current_page - 1)"
                            >
                                Anterior
                            </button>
                            <button
                                class="btn-secondary"
                                :disabled="vacationMeta.current_page >= vacationMeta.last_page"
                                @click="setPage('vacation', vacationMeta.current_page + 1)"
                            >
                                Siguiente
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    v-if="!isAdmin"
                    class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800"
                >
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Mis vacaciones por vencer (90 días)
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-left text-gray-500">
                                    <th class="px-2 py-2">Año</th>
                                    <th class="px-2 py-2">Días disponibles</th>
                                    <th class="px-2 py-2">Vencimiento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in myExpiringVacations" :key="item.id" class="border-b border-gray-100">
                                    <td class="px-2 py-2">{{ item.year }}</td>
                                    <td class="px-2 py-2">{{ item.available_days }}</td>
                                    <td class="px-2 py-2">{{ formatDate(item.expires_at) }}</td>
                                </tr>
                                <tr v-if="myExpiringVacations.length === 0">
                                    <td colspan="3" class="px-2 py-6 text-center text-gray-500">
                                        No tienes días por vencer en los próximos 90 días
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </div>

        <AbsenceModal
            :show="showAbsenceModal"
            :mode="absenceModalMode"
            :absence="selectedAbsence"
            :users="users"
            :isAdmin="isAdmin"
            @close="showAbsenceModal = false"
            @saved="loadDashboard"
        />

        <SubscriptionAnnouncementModal
            :show="showSubscriptionAnnouncement"
            :type="subscriptionAnnouncementType"
            :days-remaining="subscriptionDaysRemaining"
            :plan-name="subscriptionPlanName"
            :expires-at="subscriptionExpiresAt"
            @close="showSubscriptionAnnouncement = false"
            @renew="
                () => {
                    showSubscriptionAnnouncement = false;
                }
            "
        />
    </AppLayout>
</template>
