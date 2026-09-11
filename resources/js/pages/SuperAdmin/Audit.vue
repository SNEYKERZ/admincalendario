<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">
                        Auditoría de SuperAdmin
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400 mt-2">
                        Registro de todas las acciones realizadas
                    </p>
                </div>
                <Link
                    href="/superadmin/dashboard"
                    class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium transition-colors"
                >
                    â† Volver
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Filtros
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Acción
                        </label>
                        <select
                            v-model="filters.action"
                            @change="applyFilters"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Todas las acciones</option>
                            <option value="login">Login</option>
                            <option value="start_impersonation">Iniciar Impersonation</option>
                            <option value="end_impersonation">Finalizar Impersonation</option>
                            <option value="activate_tenant">Activar Tenant</option>
                            <option value="deactivate_tenant">Desactivar Tenant</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Desde
                        </label>
                        <input
                            v-model="filters.from_date"
                            @change="applyFilters"
                            type="date"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Hasta
                        </label>
                        <input
                            v-model="filters.to_date"
                            @change="applyFilters"
                            type="date"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                </div>
            </div>

            <!-- Audit Log Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Fecha/Hora
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Acción
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    SuperAdmin
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Impersonado
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Tenant
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Descripción
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="log in audits.data" :key="log.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    {{ log.created_at }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="[
                                            'px-3 py-1 rounded-full text-xs font-semibold',
                                            getActionBadgeClass(log.action),
                                        ]"
                                    >
                                        {{ formatAction(log.action) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm">
                                        <p class="text-gray-900 dark:text-white font-medium">
                                            {{ log.superadmin_name }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div v-if="log.impersonated_name" class="text-gray-900 dark:text-white">
                                        {{ log.impersonated_name }}
                                    </div>
                                    <div v-else class="text-gray-500 dark:text-gray-400">
                                        -
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div v-if="log.tenant_name" class="text-gray-900 dark:text-white">
                                        {{ log.tenant_name }}
                                    </div>
                                    <div v-else class="text-gray-500 dark:text-gray-400">
                                        -
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ log.description || '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="audits.data.length === 0" class="text-center py-12">
                    <p class="text-gray-500 dark:text-gray-400">
                        No hay registros de auditoría
                    </p>
                </div>

                <!-- Pagination -->
                <div v-if="audits.meta && audits.meta.last_page > 1" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-700/50">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Página {{ audits.meta.current_page }} de {{ audits.meta.last_page }}
                        ({{ audits.meta.total }} registros)
                    </p>
                    <div class="space-x-2">
                        <Link
                            v-if="audits.meta.current_page > 1"
                            :href="`/superadmin/audit?page=${audits.meta.current_page - 1}`"
                            class="px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                        >
                            â† Anterior
                        </Link>
                        <Link
                            v-if="audits.meta.current_page < audits.meta.last_page"
                            :href="`/superadmin/audit?page=${audits.meta.current_page + 1}`"
                            class="px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                        >
                            Siguiente â†’
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';

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

const props = defineProps<AuditResponse & {
    filters: {
        action: string | null;
        tenant_id: string | null;
        superadmin_id: string | null;
        from_date: string | null;
        to_date: string | null;
    };
}>();

const filters = ref({
    action: props.filters.action || '',
    tenant_id: props.filters.tenant_id || '',
    superadmin_id: props.filters.superadmin_id || '',
    from_date: props.filters.from_date || '',
    to_date: props.filters.to_date || '',
});

const applyFilters = () => {
    const params = new URLSearchParams();
    if (filters.value.action) params.append('action', filters.value.action);
    if (filters.value.tenant_id) params.append('tenant_id', filters.value.tenant_id);
    if (filters.value.from_date) params.append('from_date', filters.value.from_date);
    if (filters.value.to_date) params.append('to_date', filters.value.to_date);

    router.get(`/superadmin/audit?${params.toString()}`);
};

const formatAction = (action: string): string => {
    const actionMap: Record<string, string> = {
        login: 'Login',
        logout: 'Logout',
        start_impersonation: 'Iniciar Impersonation',
        end_impersonation: 'Finalizar Impersonation',
        activate_tenant: 'Activar Tenant',
        deactivate_tenant: 'Desactivar Tenant',
        suspend_tenant: 'Suspender Tenant',
    };
    return actionMap[action] || action;
};

const getActionBadgeClass = (action: string): string => {
    const classMap: Record<string, string> = {
        login: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        logout: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
        start_impersonation: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
        end_impersonation: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
        activate_tenant: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        deactivate_tenant: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
        suspend_tenant: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
    };
    return classMap[action] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
};
</script>


