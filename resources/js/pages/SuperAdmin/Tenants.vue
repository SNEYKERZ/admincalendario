<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">
                        Gestión de Tenants
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400 mt-2">
                        Administra todas las empresas en la plataforma
                    </p>
                </div>
                <Link
                    href="/superadmin/dashboard"
                    class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium transition-colors"
                >
                    ← Volver
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Search -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-4">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Buscar por nombre, dominio o administrador..."
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
            </div>

            <!-- Tenants Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Empresa
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Dominio
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Administrador
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Usuarios
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="tenant in filteredTenants" :key="tenant.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
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
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                @{{ tenant.slug }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    {{ tenant.domain || `${tenant.slug}.ausentra.com` }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm">
                                        <p class="text-gray-900 dark:text-white font-medium">
                                            {{ tenant.admin_name }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ tenant.admin_email }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-xs font-semibold">
                                        {{ tenant.user_count }}
                                    </span>
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
                                        {{ tenant.is_active ? '✓ Activo' : '✗ Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm space-x-3">
                                    <Link
                                        :href="`/superadmin/tenants/${tenant.id}`"
                                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
                                    >
                                        Ver
                                    </Link>
                                    <button
                                        @click="impersonate(tenant.admin_id)"
                                        :disabled="loading"
                                        class="text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300 font-medium disabled:opacity-50 transition-colors"
                                    >
                                        {{ loading ? 'Impersonando...' : 'Impersonar' }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="filteredTenants.length === 0" class="text-center py-12">
                    <p class="text-gray-500 dark:text-gray-400">
                        No se encontraron tenants
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
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
    user_count: number;
    absence_count: number;
    admin_id: number | null;
    admin_name: string;
    admin_email: string;
    created_at: string;
}

const props = defineProps<{
    tenants: Tenant[];
}>();

const searchQuery = ref('');
const { startImpersonation, loading } = useSuperAdminContext();

const filteredTenants = computed(() => {
    if (!searchQuery.value) return props.tenants;

    const query = searchQuery.value.toLowerCase();
    return props.tenants.filter(
        (t) =>
            t.name.toLowerCase().includes(query) ||
            t.slug.toLowerCase().includes(query) ||
            t.admin_name.toLowerCase().includes(query) ||
            t.admin_email.toLowerCase().includes(query)
    );
});

const impersonate = (adminId: number | null) => {
    if (!adminId) {
        alert('Este tenant no tiene administrador asignado');
        return;
    }

    if (confirm('¿Impersonar a este administrador?')) {
        startImpersonation(adminId);
    }
};
</script>


