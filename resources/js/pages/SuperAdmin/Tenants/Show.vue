<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ tenant.name }}
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400 mt-2">
                        Detalle del tenant
                    </p>
                </div>
                <Link
                    href="/superadmin/tenants"
                    class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium transition-colors"
                >
                    â† Volver a Tenants
                </Link>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Información General -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Información General
                    </h3>
                    <dl class="space-y-4">
                        <div class="flex justify-between items-start">
                            <dt class="text-gray-600 dark:text-gray-400 font-medium">Nombre</dt>
                            <dd class="text-gray-900 dark:text-white">{{ tenant.name }}</dd>
                        </div>
                        <div class="flex justify-between items-start">
                            <dt class="text-gray-600 dark:text-gray-400 font-medium">Slug</dt>
                            <dd class="text-gray-900 dark:text-white font-mono text-sm">{{ tenant.slug }}</dd>
                        </div>
                        <div class="flex justify-between items-start">
                            <dt class="text-gray-600 dark:text-gray-400 font-medium">Dominio</dt>
                            <dd class="text-gray-900 dark:text-white">
                                {{ tenant.domain || `${tenant.slug}.ausentra.com` }}
                            </dd>
                        </div>
                        <div class="flex justify-between items-start">
                            <dt class="text-gray-600 dark:text-gray-400 font-medium">Estado</dt>
                            <dd>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                    âœ“ Activo
                                </span>
                            </dd>
                        </div>
                        <div class="flex justify-between items-start">
                            <dt class="text-gray-600 dark:text-gray-400 font-medium">Creado</dt>
                            <dd class="text-gray-900 dark:text-white">{{ tenant.created_at }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Administrador -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Administrador
                    </h3>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-900 dark:text-white font-medium">{{ tenant.admin_name }}</p>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">{{ tenant.admin_email }}</p>
                        </div>
                        <button
                            @click="impersonate"
                            :disabled="loading"
                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium disabled:opacity-50 transition-colors"
                        >
                            {{ loading ? 'Impersonando...' : 'ðŸ‘¤ Impersonar' }}
                        </button>
                    </div>
                </div>

                <!-- Estadí­sticas -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Estadí­sticas
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                            <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Usuarios</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                                {{ stats.total_users }}
                            </p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                            <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Administradores</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                                {{ stats.admin_count }}
                            </p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                            <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Ausencias</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                                {{ stats.total_absences }}
                            </p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                            <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Áreas</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                                {{ stats.areas_count }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Audit Log Sidebar -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Historial de Acciones
                </h3>
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <div v-for="log in auditLog" :key="log.id" class="pb-3 border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                        <p class="text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider">
                            {{ log.action.replace('_', ' ') }}
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            {{ log.description }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                            {{ log.created_at }}
                        </p>
                    </div>
                    <div v-if="auditLog.length === 0" class="text-center py-4">
                        <p class="text-gray-500 dark:text-gray-400 text-sm">
                            Sin historial
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { useSuperAdminContext } from '@/composables/useSuperAdminContext';

interface Tenant {
    id: number;
    name: string;
    slug: string;
    domain: string | null;
    is_active: boolean;
    is_main: boolean;
    timezone: string;
    locale: string;
    created_at: string;
    admin_name: string;
    admin_email: string;
    admin_id: number | null;
}

interface Stats {
    total_users: number;
    admin_count: number;
    collaborator_count: number;
    total_absences: number;
    pending_absences: number;
    approved_absences: number;
    areas_count: number;
}

interface AuditLog {
    id: number;
    action: string;
    description: string | null;
    superadmin_name: string;
    impersonated_name: string | null;
    tenant_name: string | null;
    created_at: string;
}

const props = defineProps<{
    tenant: Tenant;
    stats: Stats;
    auditLog: AuditLog[];
}>();

const { startImpersonation, loading } = useSuperAdminContext();

const impersonate = () => {
    if (!props.tenant.admin_id) {
        alert('Este tenant no tiene administrador asignado');
        return;
    }

    if (confirm(`Â¿Impersonar a ${props.tenant.admin_name}?`)) {
        startImpersonation(props.tenant.admin_id);
    }
};
</script>

