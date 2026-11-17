<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { CalendarClock, Plus, RefreshCw } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref } from 'vue';
import AbsenceModal from '@/components/AbsenceModal.vue';
import EmptyState from '@/components/app/EmptyState.vue';
import PageContainer from '@/components/app/PageContainer.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import SectionCard from '@/components/app/SectionCard.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import DashboardFilters from '@/components/dashboard/DashboardFilters.vue';
import DashboardMetrics from '@/components/dashboard/DashboardMetrics.vue';
import DashboardTableCard from '@/components/dashboard/DashboardTableCard.vue';
import type {
    AbsenceItem,
    AreaItem,
    DashboardResponse,
    EmployeeStatusItem,
    ExpiringVacationItem,
    MetricOrganization,
    MetricPersonal,
    MetricSuperadmin,
    PaginationMeta,
    UserItem,
    VacationBalanceItem,
    Viewer,
} from '@/components/dashboard/types';
import SubscriptionAnnouncementModal from '@/components/SubscriptionAnnouncementModal.vue';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { useNotification } from '@/composables/useNotification';
import AppLayout from '@/layouts/AppLayout.vue';

type TableKey = 'status' | 'recent' | 'pending' | 'vacation';
interface TableState {
    search: string;
    page: number;
    perPage: number;
}

const emptyMeta = (): PaginationMeta => ({
    current_page: 1,
    last_page: 1,
    per_page: 5,
    total: 0,
});

const notify = useNotification();

const loading = ref(true);
const loaded = ref(false);
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
const employeeMeta = ref<PaginationMeta>(emptyMeta());

const recentAbsences = ref<AbsenceItem[]>([]);
const recentMeta = ref<PaginationMeta>(emptyMeta());

const pendingApprovals = ref<AbsenceItem[]>([]);
const pendingMeta = ref<PaginationMeta>(emptyMeta());

const vacationBalances = ref<VacationBalanceItem[]>([]);
const vacationMeta = ref<PaginationMeta>(emptyMeta());

const myExpiringVacations = ref<ExpiringVacationItem[]>([]);

const tableStates = reactive<Record<TableKey, TableState>>({
    status: { search: '', page: 1, perPage: 5 },
    recent: { search: '', page: 1, perPage: 5 },
    pending: { search: '', page: 1, perPage: 5 },
    vacation: { search: '', page: 1, perPage: 5 },
});

const isAdmin = computed(() => viewer.value?.is_admin ?? false);
const isSuperAdmin = computed(() => viewer.value?.is_superadmin ?? false);
const initialLoading = computed(() => loading.value && !loaded.value);

const showAbsenceModal = ref(false);
const absenceModalMode = ref<'create' | 'view'>('create');
const selectedAbsence = ref<any>(null);

const showSubscriptionAnnouncement = ref(false);
/** El aviso de suscripción se muestra una sola vez por visita, no en cada recarga. */
const subscriptionAnnouncementShown = ref(false);
const subscriptionAnnouncementType = ref<'expiring' | 'payment_required'>(
    'expiring',
);
const subscriptionDaysRemaining = ref(0);
const subscriptionPlanName = ref('');
const subscriptionExpiresAt = ref('');

const evaluateSubscriptionAnnouncement = () => {
    if (subscriptionAnnouncementShown.value) return;
    if (!subscription.value || !isAdmin.value || isSuperAdmin.value) return;

    if (
        subscription.value.show_ad &&
        (subscription.value.days_remaining ?? 0) > 0
    ) {
        subscriptionAnnouncementType.value = 'expiring';
        subscriptionDaysRemaining.value = subscription.value.days_remaining ?? 0;
        subscriptionPlanName.value = subscription.value.plan_name ?? '';
        subscriptionExpiresAt.value = subscription.value.expires_at ?? '';
        showSubscriptionAnnouncement.value = true;
        subscriptionAnnouncementShown.value = true;
    } else if (!subscription.value.has_subscription) {
        subscriptionAnnouncementType.value = 'payment_required';
        subscriptionDaysRemaining.value = 0;
        subscriptionPlanName.value = '';
        subscriptionExpiresAt.value = '';
        showSubscriptionAnnouncement.value = true;
        subscriptionAnnouncementShown.value = true;
    }
};

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

        const response = await axios.get<DashboardResponse>(
            '/dashboard/data',
            { params },
        );

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
        vacationMeta.value = data.tables.vacation_balances?.meta ?? emptyMeta();

        myExpiringVacations.value = data.tables.my_expiring_vacations ?? [];

        loaded.value = true;
        evaluateSubscriptionAnnouncement();
    } catch (error) {
        console.error('Error loading dashboard:', error);
        notify.error('No se pudo cargar el panel. Inténtalo de nuevo.');
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

const setSearch = (table: TableKey, value: string) => {
    if (tableStates[table].search === value) return;
    tableStates[table].search = value;
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

const openCreate = () => {
    absenceModalMode.value = 'create';
    selectedAbsence.value = null;
    showAbsenceModal.value = true;
};

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('es-CO', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });

const availabilityChips = computed(() => [
    {
        key: 'green',
        label: 'Disponibles',
        value: employeeSummary.value.green,
        tone: 'success' as const,
    },
    {
        key: 'yellow',
        label: 'Con ausencia próxima',
        value: employeeSummary.value.yellow,
        tone: 'warning' as const,
    },
    {
        key: 'red',
        label: 'Ausentes hoy',
        value: employeeSummary.value.red,
        tone: 'destructive' as const,
    },
]);

onMounted(loadDashboard);
</script>

<template>
    <Head title="Inicio" />
    <AppLayout>
        <PageContainer>
            <PageHeader
                :title="
                    viewer ? `Hola, ${viewer.name.split(' ')[0]}` : 'Inicio'
                "
                :description="
                    isAdmin
                        ? 'Disponibilidad del equipo, aprobaciones pendientes y saldos de vacaciones.'
                        : 'Tus vacaciones, solicitudes pendientes y próximas ausencias.'
                "
            >
                <template #actions>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="loading"
                        aria-label="Actualizar datos"
                        @click="loadDashboard"
                    >
                        <RefreshCw :class="loading ? 'animate-spin' : ''" />
                        <span class="hidden sm:inline">Actualizar</span>
                    </Button>
                    <Button v-if="isAdmin" size="sm" @click="openCreate">
                        <Plus /> Nueva ausencia
                    </Button>
                </template>
            </PageHeader>

            <div
                v-if="
                    isAdmin &&
                    subscription &&
                    subscription.has_subscription &&
                    subscription.show_ad
                "
                role="status"
                class="flex items-center gap-2 rounded-lg border border-warning/25 bg-warning-soft px-4 py-2.5 text-sm text-warning"
            >
                <CalendarClock class="size-4 shrink-0" aria-hidden="true" />
                <span>
                    Tu plan <strong>{{ subscription.plan_name }}</strong> vence
                    en {{ subscription.days_remaining }} días.
                </span>
            </div>

            <DashboardFilters
                v-if="isAdmin"
                v-model:user="selectedUser"
                v-model:area="selectedArea"
                :users="users"
                :areas="areas"
                :disabled="loading"
                @change="applyGlobalFilters"
            />

            <DashboardMetrics
                :personal="personalMetrics"
                :organization="organizationMetrics"
                :superadmin="superadminMetrics"
                :is-admin="isAdmin"
                :is-super-admin="isSuperAdmin"
                :loading="initialLoading"
            />

            <div class="grid gap-6 xl:grid-cols-2">
                <!-- Aprobaciones / mis pendientes -->
                <DashboardTableCard
                    id-prefix="pending"
                    :title="
                        isAdmin
                            ? 'Aprobaciones pendientes'
                            : 'Mis solicitudes pendientes'
                    "
                    :search="tableStates.pending.search"
                    :per-page="tableStates.pending.perPage"
                    :meta="pendingMeta"
                    :loading="loading"
                    :initial-loading="initialLoading"
                    search-placeholder="Buscar pendiente…"
                    @search="setSearch('pending', $event)"
                    @per-page="setPerPage('pending', $event)"
                    @page="setPage('pending', $event)"
                >
                    <table v-if="pendingApprovals.length" class="data-table">
                        <thead>
                            <tr>
                                <th v-if="isAdmin">Colaborador</th>
                                <th>Tipo</th>
                                <th>Fechas</th>
                                <th class="text-right">Días</th>
                                <th>Solicitado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="absence in pendingApprovals"
                                :key="absence.id"
                            >
                                <td v-if="isAdmin" class="font-medium">
                                    {{ absence.user.name }}
                                </td>
                                <td>{{ absence.type.name || '—' }}</td>
                                <td class="whitespace-nowrap">
                                    {{ formatDate(absence.start) }} –
                                    {{ formatDate(absence.end) }}
                                </td>
                                <td class="text-right tabular-nums">
                                    {{ absence.days }}
                                </td>
                                <td
                                    class="whitespace-nowrap text-muted-foreground"
                                >
                                    {{ absence.requested_at ?? '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <EmptyState
                        v-else
                        compact
                        title="Nada pendiente"
                        :description="
                            tableStates.pending.search
                                ? 'Ninguna solicitud coincide con la búsqueda.'
                                : 'No hay solicitudes esperando aprobación.'
                        "
                    />
                </DashboardTableCard>

                <!-- Ausencias recientes -->
                <DashboardTableCard
                    id-prefix="recent"
                    :title="
                        isAdmin
                            ? 'Ausencias recientes del equipo'
                            : 'Mis ausencias recientes'
                    "
                    :search="tableStates.recent.search"
                    :per-page="tableStates.recent.perPage"
                    :meta="recentMeta"
                    :loading="loading"
                    :initial-loading="initialLoading"
                    search-placeholder="Buscar ausencia…"
                    @search="setSearch('recent', $event)"
                    @per-page="setPerPage('recent', $event)"
                    @page="setPage('recent', $event)"
                >
                    <table v-if="recentAbsences.length" class="data-table">
                        <thead>
                            <tr>
                                <th v-if="isAdmin">Colaborador</th>
                                <th>Tipo</th>
                                <th>Fechas</th>
                                <th class="text-right">Días</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="absence in recentAbsences"
                                :key="absence.id"
                            >
                                <td v-if="isAdmin" class="font-medium">
                                    {{ absence.user.name }}
                                </td>
                                <td>{{ absence.type.name || '—' }}</td>
                                <td class="whitespace-nowrap">
                                    {{ formatDate(absence.start) }} –
                                    {{ formatDate(absence.end) }}
                                </td>
                                <td class="text-right tabular-nums">
                                    {{ absence.days }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <EmptyState
                        v-else
                        compact
                        title="Sin ausencias recientes"
                        :description="
                            tableStates.recent.search
                                ? 'Ninguna ausencia coincide con la búsqueda.'
                                : 'Aquí verás las últimas ausencias registradas.'
                        "
                    />
                </DashboardTableCard>
            </div>

            <!-- Disponibilidad -->
            <DashboardTableCard
                id-prefix="status"
                title="Disponibilidad del equipo"
                description="Quién está disponible hoy y quién tiene una ausencia próxima."
                :search="tableStates.status.search"
                :per-page="tableStates.status.perPage"
                :meta="employeeMeta"
                :loading="loading"
                :initial-loading="initialLoading"
                search-placeholder="Buscar colaborador…"
                @search="setSearch('status', $event)"
                @per-page="setPerPage('status', $event)"
                @page="setPage('status', $event)"
            >
                <template #before>
                    <div
                        class="flex flex-wrap items-center gap-x-5 gap-y-2 border-b px-4 py-2.5 text-[13px] sm:px-5"
                    >
                        <template v-if="initialLoading">
                            <Skeleton class="h-4 w-64" />
                        </template>
                        <template v-else>
                            <span
                                v-for="chip in availabilityChips"
                                :key="chip.key"
                                class="flex items-center gap-2"
                            >
                                <StatusBadge
                                    :tone="chip.tone"
                                    :label="String(chip.value)"
                                    class="tabular-nums"
                                />
                                <span class="text-muted-foreground">{{
                                    chip.label
                                }}</span>
                            </span>
                            <span class="text-muted-foreground sm:ml-auto">
                                Total:
                                <strong class="text-foreground tabular-nums">{{
                                    employeeSummary.total
                                }}</strong>
                            </span>
                        </template>
                    </div>
                </template>

                <table v-if="employeeStatuses.length" class="data-table">
                    <thead>
                        <tr>
                            <th>Colaborador</th>
                            <th>Estado</th>
                            <th>Ausencia actual</th>
                            <th>Próxima ausencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="employee in employeeStatuses" :key="employee.id">
                            <td class="font-medium">{{ employee.name }}</td>
                            <td><StatusBadge :status="employee.status" /></td>
                            <td class="text-muted-foreground">
                                <span v-if="employee.current_absence">
                                    {{ employee.current_absence.type }} · hasta
                                    {{ formatDate(employee.current_absence.end) }}
                                </span>
                                <span v-else>—</span>
                            </td>
                            <td class="text-muted-foreground">
                                <span v-if="employee.upcoming_absence">
                                    {{ employee.upcoming_absence.type }} · desde
                                    {{
                                        formatDate(employee.upcoming_absence.start)
                                    }}
                                </span>
                                <span v-else>—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <EmptyState
                    v-else
                    compact
                    title="Sin resultados"
                    description="No hay colaboradores que coincidan con los filtros."
                />
            </DashboardTableCard>

            <!-- Balance de vacaciones (admin) -->
            <DashboardTableCard
                v-if="isAdmin"
                id-prefix="vacation"
                title="Saldo de vacaciones por colaborador"
                :search="tableStates.vacation.search"
                :per-page="tableStates.vacation.perPage"
                :meta="vacationMeta"
                :loading="loading"
                :initial-loading="initialLoading"
                search-placeholder="Buscar colaborador…"
                @search="setSearch('vacation', $event)"
                @per-page="setPerPage('vacation', $event)"
                @page="setPage('vacation', $event)"
            >
                <table v-if="vacationBalances.length" class="data-table">
                    <thead>
                        <tr>
                            <th>Colaborador</th>
                            <th class="text-right">Días disponibles</th>
                            <th>Próximos vencimientos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in vacationBalances" :key="item.id">
                            <td class="font-medium">{{ item.name }}</td>
                            <td class="text-right tabular-nums">
                                {{ item.available }}
                            </td>
                            <td>
                                <div
                                    v-if="item.expiring_soon.length"
                                    class="flex flex-wrap gap-1.5"
                                >
                                    <StatusBadge
                                        v-for="row in item.expiring_soon"
                                        :key="`${row.year}-${row.expires}`"
                                        tone="warning"
                                        :dot="false"
                                        :label="`${row.days} días · ${formatDate(row.expires)}`"
                                    />
                                </div>
                                <span v-else class="text-muted-foreground"
                                    >—</span
                                >
                            </td>
                        </tr>
                    </tbody>
                </table>
                <EmptyState
                    v-else
                    compact
                    title="Sin resultados"
                    description="No hay saldos de vacaciones para mostrar."
                />
            </DashboardTableCard>

            <!-- Mis vacaciones por vencer (colaborador) -->
            <SectionCard
                v-if="!isAdmin && loaded"
                title="Mis vacaciones por vencer"
                description="Días que vencen en los próximos 90 días."
                flush
            >
                <div v-if="myExpiringVacations.length" class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Periodo</th>
                                <th class="text-right">Días disponibles</th>
                                <th>Vencimiento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in myExpiringVacations"
                                :key="item.id"
                            >
                                <td class="tabular-nums">{{ item.year }}</td>
                                <td class="text-right tabular-nums">
                                    {{ item.available_days }}
                                </td>
                                <td>
                                    <StatusBadge
                                        tone="warning"
                                        :label="formatDate(item.expires_at)"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <EmptyState
                    v-else
                    compact
                    :icon="CalendarClock"
                    title="Nada por vencer"
                    description="No tienes días de vacaciones que venzan en los próximos 90 días."
                />
            </SectionCard>
        </PageContainer>

        <AbsenceModal
            :show="showAbsenceModal"
            :mode="absenceModalMode"
            :absence="selectedAbsence"
            :users="users"
            :is-admin="isAdmin"
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
            @renew="showSubscriptionAnnouncement = false"
        />
    </AppLayout>
</template>
