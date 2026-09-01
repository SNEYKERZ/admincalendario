<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Gestión de Solicitudes</h2>
                    <p class="text-gray-500 dark:text-gray-400 mt-2">Revisa y aprueba las solicitudes de empleados</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Filtros -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Filtro Estado -->
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-2">
                            Estado
                        </label>
                        <select
                            v-model="selectedStatus"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Todos los estados</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="aprobado">Aprobado</option>
                            <option value="rechazado">Rechazado</option>
                        </select>
                    </div>

                    <!-- Filtro Tipo -->
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-2">
                            Tipo de Solicitud
                        </label>
                        <select
                            v-model="selectedType"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Todos los tipos</option>
                            <option v-for="(label, key) in requestTypes" :key="key" :value="key">
                                {{ label }}
                            </option>
                        </select>
                    </div>

                    <!-- Búsqueda -->
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-2">
                            Búsqueda
                        </label>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Nombre o asunto..."
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>

                    <!-- Botón Reset -->
                    <div class="flex items-end">
                        <button
                            @click="resetFilters"
                            class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                        >
                            Limpiar Filtros
                        </button>
                    </div>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ requests.data.length }}</p>
                </div>
                <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg shadow border border-yellow-200 dark:border-yellow-800 p-4">
                    <p class="text-sm text-yellow-700 dark:text-yellow-300">Pendientes</p>
                    <p class="text-2xl font-bold text-yellow-900 dark:text-yellow-100 mt-1">
                        {{ requests.data.filter((r) => r.status === 'pendiente').length }}
                    </p>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg shadow border border-green-200 dark:border-green-800 p-4">
                    <p class="text-sm text-green-700 dark:text-green-300">Aprobadas</p>
                    <p class="text-2xl font-bold text-green-900 dark:text-green-100 mt-1">
                        {{ requests.data.filter((r) => r.status === 'aprobado').length }}
                    </p>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg shadow border border-red-200 dark:border-red-800 p-4">
                    <p class="text-sm text-red-700 dark:text-red-300">Rechazadas</p>
                    <p class="text-2xl font-bold text-red-900 dark:text-red-100 mt-1">
                        {{ requests.data.filter((r) => r.status === 'rechazado').length }}
                    </p>
                </div>
            </div>

            <!-- Tabla de Solicitudes -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Empleado
                                </th>
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
                                    <div class="text-sm">
                                        <p class="font-medium text-gray-900 dark:text-white">{{ request.user_name }}</p>
                                        <p class="text-gray-500 dark:text-gray-400">{{ request.user_email }}</p>
                                    </div>
                                </td>
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex gap-2">
                                        <button
                                            @click="selectedRequest = request; showModal = true"
                                            class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded font-medium transition-colors text-xs"
                                        >
                                            Gestionar
                                        </button>
                                    </div>
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
        </div>

        <!-- Modal de Gestión -->
        <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-2xl w-full max-h-screen overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between sticky top-0 bg-white dark:bg-gray-800">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ selectedRequest?.title }}</h3>
                    <button
                        @click="showModal = false"
                        class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 text-2xl leading-none"
                    >
                        ×
                    </button>
                </div>

                <div v-if="selectedRequest" class="p-6 space-y-6">
                    <!-- Información de Empleado -->
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Empleado</h4>
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded">
                            <p class="text-gray-900 dark:text-white font-medium">{{ selectedRequest.user_name }}</p>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">{{ selectedRequest.user_email }}</p>
                        </div>
                    </div>

                    <!-- Detalles de Solicitud -->
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Detalles</h4>
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">Tipo:</span>
                                <p class="text-gray-900 dark:text-white">{{ getTypeLabel(selectedRequest.request_type) }}</p>
                            </div>
                            <div v-if="selectedRequest.description">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Descripción:</span>
                                <p class="text-gray-900 dark:text-white whitespace-pre-wrap mt-1">{{ selectedRequest.description }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div v-if="selectedRequest.status === 'pendiente'" class="border-t border-gray-200 dark:border-gray-700 pt-6 space-y-4">
                        <!-- Aprobar -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-2">
                                Comentario de Aprobación (Opcional)
                            </label>
                            <textarea
                                v-model="approvalComment"
                                placeholder="Ej: Certificado generado..."
                                rows="3"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <button
                            @click="approveRequest"
                            :disabled="approvingLoading"
                            class="w-full px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50"
                        >
                            {{ approvingLoading ? 'Aprobando...' : '✓ Aprobar Solicitud' }}
                        </button>

                        <!-- Rechazar -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-2">
                                Razón del Rechazo *
                            </label>
                            <textarea
                                v-model="rejectionReason"
                                placeholder="Explica por qué se rechaza la solicitud..."
                                rows="3"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required
                            />
                        </div>
                        <button
                            @click="rejectRequest"
                            :disabled="rejectingLoading || !rejectionReason"
                            class="w-full px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50"
                        >
                            {{ rejectingLoading ? 'Rechazando...' : '✗ Rechazar Solicitud' }}
                        </button>
                    </div>

                    <!-- Historial de Decisiones -->
                    <div v-if="selectedRequest.approvals && selectedRequest.approvals.length > 0" class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Historial de Decisiones</h4>
                        <div class="space-y-3">
                            <div
                                v-for="approval in selectedRequest.approvals"
                                :key="approval.id"
                                :class="[
                                    'p-4 rounded-lg border',
                                    approval.status === 'aprobado'
                                        ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800'
                                        : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
                                ]"
                            >
                                <div class="flex items-start justify-between mb-2">
                                    <p
                                        :class="[
                                            'font-semibold',
                                            approval.status === 'aprobado'
                                                ? 'text-green-900 dark:text-green-100'
                                                : 'text-red-900 dark:text-red-100',
                                        ]"
                                    >
                                        {{ approval.status === 'aprobado' ? '✓ Aprobado' : '✗ Rechazado' }}
                                    </p>
                                    <span
                                        :class="[
                                            'text-xs',
                                            approval.status === 'aprobado'
                                                ? 'text-green-700 dark:text-green-300'
                                                : 'text-red-700 dark:text-red-300',
                                        ]"
                                    >
                                        {{ formatDate(approval.decided_at) }}
                                    </span>
                                </div>
                                <p
                                    :class="[
                                        'text-sm',
                                        approval.status === 'aprobado' ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200',
                                    ]"
                                >
                                    Decidido por: {{ approval.approver_name }}
                                </p>
                                <p
                                    v-if="approval.comment"
                                    :class="[
                                        'text-sm mt-2',
                                        approval.status === 'aprobado' ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300',
                                    ]"
                                >
                                    {{ approval.comment }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';

interface RequestApproval {
    id: number;
    status: string;
    comment: string | null;
    decided_at: string;
    approver_name: string;
}

interface EmployeeRequest {
    id: number;
    request_type: string;
    status: string;
    title: string;
    description: string | null;
    created_at: string;
    user_name: string;
    user_email: string;
    approvals?: RequestApproval[];
}

interface PaginatedRequests {
    data: EmployeeRequest[];
}

const props = defineProps<{
    requests: PaginatedRequests;
}>();

const selectedStatus = ref('');
const selectedType = ref('');
const searchQuery = ref('');
const showModal = ref(false);
const selectedRequest = ref<EmployeeRequest | null>(null);
const approvalComment = ref('');
const rejectionReason = ref('');
const approvingLoading = ref(false);
const rejectingLoading = ref(false);

const requestTypes = {
    certificado: 'Certificado Laboral',
    permiso_especial: 'Permiso Especial',
    documento: 'Documento',
    cambio_datos: 'Cambio de Datos',
    otro: 'Otra Solicitud',
};

const typeLabels: Record<string, string> = requestTypes;

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

const filteredRequests = computed(() => {
    return props.requests.data.filter((request) => {
        const statusMatch = !selectedStatus.value || request.status === selectedStatus.value;
        const typeMatch = !selectedType.value || request.request_type === selectedType.value;
        const searchMatch =
            !searchQuery.value ||
            request.user_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            request.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            request.user_email.toLowerCase().includes(searchQuery.value.toLowerCase());

        return statusMatch && typeMatch && searchMatch;
    });
});

const resetFilters = () => {
    selectedStatus.value = '';
    selectedType.value = '';
    searchQuery.value = '';
};

const approveRequest = () => {
    if (!selectedRequest.value) return;

    approvingLoading.value = true;
    router.post(`/admin/solicitudes/${selectedRequest.value.id}/aprobar`, {
        comment: approvalComment.value || null,
    });
};

const rejectRequest = () => {
    if (!selectedRequest.value || !rejectionReason.value.trim()) return;

    rejectingLoading.value = true;
    router.post(`/admin/solicitudes/${selectedRequest.value.id}/rechazar`, {
        reason: rejectionReason.value,
    });
};
</script>
