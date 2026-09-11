<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">
                        Panel Super Administrador
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400 mt-2">
                        Gestiona todos los tenants de la plataforma
                    </p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Tenants -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                Total de Tenants
                            </p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                                {{ stats.total_tenants }}
                            </p>
                        </div>
                        <div class="bg-blue-100 dark:bg-blue-900/30 rounded-lg p-3">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Active Tenants -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                Tenants Activos
                            </p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                                {{ stats.active_tenants }}
                            </p>
                        </div>
                        <div class="bg-green-100 dark:bg-green-900/30 rounded-lg p-3">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Users -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                Total de Usuarios
                            </p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                                {{ stats.total_users }}
                            </p>
                        </div>
                        <div class="bg-purple-100 dark:bg-purple-900/30 rounded-lg p-3">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Pending Absences -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                Ausencias/Novedades Pendientes
                            </p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                                {{ stats.pending_absences }}
                            </p>
                        </div>
                        <div class="bg-yellow-100 dark:bg-yellow-900/30 rounded-lg p-3">
                            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Link
                    href="/superadmin/tenants"
                    class="flex items-center justify-between px-6 py-4 bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 hover:shadow-md hover:border-blue-300 dark:hover:border-blue-600 transition-all"
                >
                    <span class="font-medium text-gray-900 dark:text-white">Gestionar Tenants</span>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </Link>
                <Link
                    href="/superadmin/audit"
                    class="flex items-center justify-between px-6 py-4 bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 hover:shadow-md hover:border-blue-300 dark:hover:border-blue-600 transition-all"
                >
                    <span class="font-medium text-gray-900 dark:text-white">Ver Auditoría</span>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </Link>
                <Link
                    href="/gestion-sistema"
                    class="flex items-center justify-between px-6 py-4 bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 hover:shadow-md hover:border-blue-300 dark:hover:border-blue-600 transition-all"
                >
                    <span class="font-medium text-gray-900 dark:text-white">Config. Global</span>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </Link>
            </div>

            <!-- Recent Tenants Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Ášltimos Tenants
                    </h3>
                    <Link
                        href="/superadmin/tenants"
                        class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
                    >
                        Ver todos â†’
                    </Link>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Empresa
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Dominio
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Admin
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Acción
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="tenant in recentTenants" :key="tenant.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                                            <span class="text-white font-bold text-sm">
                                                {{ tenant.name.charAt(0).toUpperCase() }}
                                            </span>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ tenant.name }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    {{ tenant.domain }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm">
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ tenant.admin_name }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="[
                                            'px-3 py-1 rounded-full text-xs font-semibold',
                                            tenant.is_active
                                                ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                                                : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                        ]"
                                    >
                                        {{ tenant.is_active ? 'âœ“ Activo' : 'âœ— Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <Link
                                        :href="`/superadmin/tenants/${tenant.id}`"
                                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
                                    >
                                        Ver
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="recentTenants.length === 0" class="text-center py-8">
                    <p class="text-gray-500 dark:text-gray-400">
                        No hay tenants disponibles
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

interface Stats {
    total_tenants: number;
    active_tenants: number;
    inactive_tenants: number;
    total_users: number;
    total_admins: number;
    total_absences: number;
    pending_absences: number;
}

interface Tenant {
    id: number;
    name: string;
    slug: string;
    domain: string;
    is_active: boolean;
    created_at: string;
    admin_name: string;
}

defineProps<{
    stats: Stats;
    recentTenants: Tenant[];
}>();
</script>


