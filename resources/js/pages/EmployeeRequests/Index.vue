<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Mis Solicitudes</h2>
                    <p class="text-gray-500 dark:text-gray-400 mt-2">Gestiona tus solicitudes a Recursos Humanos</p>
                </div>
                <Link
                    href="/solicitudes/crear"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
                >
                    + Nueva Solicitud
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Filtro de Estado -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex gap-2 flex-wrap">
                    <button
                        v-for="status in ['todas', 'pendiente', 'aprobado', 'rechazado']"
                        :key="status"
                        @click="selectedStatus = status"
                        :class="[
                            'px-4 py-2 rounded-lg font-medium transition-colors',
                            selectedStatus === status
                                ? 'bg-blue-600 text-white'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600',
                        ]"
                    >
                        {{ statusLabels[status] }}
                    </button>
                </div>
            </div>

            <!-- Tabla de Solicitudes -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Tipo
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Asunto
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Fecha
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="request in filteredRequests" :key="request.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ getTypeLabel(request.request_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 max-w-xs">
                                    <p class="text-sm text-gray-900 dark:text-white truncate">{{ request.title }}</p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['px-3 py-1 rounded-full text-xs font-semibold', getStatusClass(request.status)]">
                                        {{ getStatusLabel(request.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    {{ formatDate(request.created_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm space-x-3">
                                    <Link
                                        :href="`/solicitudes/${request.id}`"
                                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
                                    >
                                        Ver
                                    </Link>
                                    <button
                                        v-if="request.status === 'pendiente'"
                                        @click="deleteRequest(request.id)"
                                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 font-medium"
                                    >
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="filteredRequests.length === 0" class="text-center py-12">
                    <p class="text-gray-500 dark:text-gray-400">No hay solicitudes</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="requests.links && requests.links.length > 3" class="flex items-center justify-between">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Página {{ requests.current_page }} de {{ requests.last_page }}
                </p>
                <div class="space-x-2">
                    <Link
                        v-if="requests.prev_page_url"
                        :href="requests.prev_page_url"
                        class="px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        ← Anterior
                    </Link>
                    <Link
                        v-if="requests.next_page_url"
                        :href="requests.next_page_url"
                        class="px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        Siguiente →
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';

interface EmployeeRequest {
    id: number;
    request_type: string;
    status: string;
    title: string;
    description: string | null;
    created_at: string;
    updated_at: string;
}

interface PaginatedRequests {
    data: EmployeeRequest[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

const props = defineProps<{
    requests: PaginatedRequests;
}>();

const selectedStatus = ref('todas');

const statusLabels = {
    todas: 'Todas',
    pendiente: 'Pendiente',
    aprobado: 'Aprobado',
    rechazado: 'Rechazado',
};

const typeLabels: Record<string, string> = {
    certificado: 'Certificado Laboral',
    permiso_especial: 'Permiso Especial',
    documento: 'Documento',
    cambio_datos: 'Cambio de Datos',
    otro: 'Otra Solicitud',
};

const filteredRequests = computed(() => {
    if (selectedStatus.value === 'todas') {
        return props.requests.data;
    }
    return props.requests.data.filter((r) => r.status === selectedStatus.value);
});

const getTypeLabel = (type: string): string => typeLabels[type] || type;

const getStatusLabel = (status: string): string => {
    return { pendiente: 'Pendiente', aprobado: 'Aprobado', rechazado: 'Rechazado' }[status] || status;
};

const getStatusClass = (status: string): string => {
    return {
        pendiente: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        aprobado: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        rechazado: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    }[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300';
};

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const deleteRequest = (id: number) => {
    if (confirm('¿Estás seguro de que deseas eliminar esta solicitud?')) {
        router.delete(`/solicitudes/${id}`);
    }
};
</script>
