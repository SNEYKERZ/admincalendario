<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, History, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import EmptyState from '@/components/app/EmptyState.vue';
import FormField from '@/components/app/FormField.vue';
import PageContainer from '@/components/app/PageContainer.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import SectionCard from '@/components/app/SectionCard.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import TablePagination from '@/components/app/TablePagination.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import type { StatusTone } from '@/lib/status';

interface AuditLog {
    id: number;
    action: string;
    description: string | null;
    superadmin_name: string;
    superadmin_email: string;
    impersonated_name: string | null;
    impersonated_email: string | null;
    tenant_name: string | null;
    ip_address: string | null;
    created_at: string;
}

interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface AuditResponse {
    data: AuditLog[];
    meta: PaginationMeta;
}

const props = defineProps<
    AuditResponse & {
        filters: {
            action: string | null;
            tenant_id: string | null;
            superadmin_id: string | null;
            from_date: string | null;
            to_date: string | null;
        };
    }
>();

const filters = ref({
    action: props.filters?.action || '',
    tenant_id: props.filters?.tenant_id || '',
    superadmin_id: props.filters?.superadmin_id || '',
    from_date: props.filters?.from_date || '',
    to_date: props.filters?.to_date || '',
});

const hasFilters = computed(
    () =>
        !!(
            filters.value.action ||
            filters.value.tenant_id ||
            filters.value.from_date ||
            filters.value.to_date
        ),
);

const buildParams = (page?: number) => {
    const params: Record<string, string | number> = {};
    if (filters.value.action) params.action = filters.value.action;
    if (filters.value.tenant_id) params.tenant_id = filters.value.tenant_id;
    if (filters.value.from_date) params.from_date = filters.value.from_date;
    if (filters.value.to_date) params.to_date = filters.value.to_date;
    if (page && page > 1) params.page = page;
    return params;
};

const applyFilters = () => {
    router.get('/superadmin/audit', buildParams(), {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    filters.value = {
        action: '',
        tenant_id: '',
        superadmin_id: '',
        from_date: '',
        to_date: '',
    };
    applyFilters();
};

const goToPage = (page: number) => {
    router.get('/superadmin/audit', buildParams(page), {
        preserveState: true,
    });
};

const ACTIONS: Record<string, { label: string; tone: StatusTone }> = {
    login: { label: 'Inicio de sesión', tone: 'info' },
    logout: { label: 'Cierre de sesión', tone: 'neutral' },
    start_impersonation: { label: 'Inicio de suplantación', tone: 'primary' },
    end_impersonation: { label: 'Fin de suplantación', tone: 'primary' },
    activate_tenant: { label: 'Empresa activada', tone: 'success' },
    deactivate_tenant: { label: 'Empresa desactivada', tone: 'destructive' },
    suspend_tenant: { label: 'Empresa suspendida', tone: 'warning' },
};

const actionLabel = (action: string) =>
    ACTIONS[action]?.label ?? action.replace(/_/g, ' ');
const actionTone = (action: string): StatusTone =>
    ACTIONS[action]?.tone ?? 'neutral';
</script>

<template>
    <Head title="Auditoría" />
    <AppLayout>
        <PageContainer>
            <PageHeader
                title="Auditoría"
                description="Registro de las acciones realizadas por los superadministradores."
                :icon="History"
            >
                <template #actions>
                    <Button variant="outline" as-child>
                        <Link href="/superadmin/dashboard">
                            <ArrowLeft /> Volver al resumen
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <SectionCard flush>
                <template #header>
                    <div
                        class="grid w-full gap-3 sm:grid-cols-[minmax(0,14rem)_minmax(0,10rem)_minmax(0,10rem)_auto] sm:items-end"
                    >
                        <FormField label="Acción" for="audit_action">
                            <select
                                id="audit_action"
                                v-model="filters.action"
                                class="input"
                                @change="applyFilters"
                            >
                                <option value="">Todas las acciones</option>
                                <option
                                    v-for="(def, key) in ACTIONS"
                                    :key="key"
                                    :value="key"
                                >
                                    {{ def.label }}
                                </option>
                            </select>
                        </FormField>
                        <FormField label="Desde" for="audit_from">
                            <Input
                                id="audit_from"
                                v-model="filters.from_date"
                                type="date"
                                @change="applyFilters"
                            />
                        </FormField>
                        <FormField label="Hasta" for="audit_to">
                            <Input
                                id="audit_to"
                                v-model="filters.to_date"
                                type="date"
                                @change="applyFilters"
                            />
                        </FormField>
                        <Button
                            v-if="hasFilters"
                            variant="ghost"
                            class="justify-self-start"
                            @click="clearFilters"
                        >
                            <X /> Limpiar filtros
                        </Button>
                    </div>
                </template>

                <EmptyState
                    v-if="data.length === 0"
                    :icon="History"
                    title="No hay registros de auditoría"
                    :description="
                        hasFilters
                            ? 'Ningún registro coincide con los filtros aplicados.'
                            : 'Las acciones de los superadministradores aparecerán aquí.'
                    "
                />

                <div v-else class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Fecha y hora</th>
                                <th>Acción</th>
                                <th>Superadmin</th>
                                <th>Suplantado</th>
                                <th>Empresa</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in data" :key="log.id">
                                <td
                                    class="whitespace-nowrap text-muted-foreground tabular-nums"
                                >
                                    {{ log.created_at }}
                                </td>
                                <td>
                                    <StatusBadge
                                        :dot="false"
                                        :tone="actionTone(log.action)"
                                        :label="actionLabel(log.action)"
                                    />
                                </td>
                                <td class="whitespace-nowrap font-medium">
                                    {{ log.superadmin_name }}
                                </td>
                                <td class="whitespace-nowrap">
                                    {{ log.impersonated_name || '—' }}
                                </td>
                                <td class="whitespace-nowrap">
                                    {{ log.tenant_name || '—' }}
                                </td>
                                <td class="min-w-[16rem] text-muted-foreground">
                                    {{ log.description || '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <template v-if="meta && meta.last_page > 1" #footer>
                    <TablePagination
                        class="w-full"
                        :page="meta.current_page"
                        :last-page="meta.last_page"
                        :total="meta.total"
                        @change="goToPage"
                    />
                </template>
            </SectionCard>
        </PageContainer>
    </AppLayout>
</template>
