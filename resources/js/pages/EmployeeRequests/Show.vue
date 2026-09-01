<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link href="/solicitudes" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 font-medium">
                    ← Volver
                </Link>
                <div class="flex-1">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">{{ request.title }}</h2>
                    <p class="text-gray-500 dark:text-gray-400 mt-2">{{ getTypeLabel(request.request_type) }}</p>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Contenido Principal -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Estado -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Estado Actual</h3>
                        <span :class="['px-4 py-2 rounded-full font-semibold text-sm', getStatusClass(request.status)]">
                            {{ getStatusLabel(request.status) }}
                        </span>
                    </div>

                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Creada el:</span>
                            <span class="text-gray-900 dark:text-white font-medium">{{ formatDate(request.created_at) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Última actualización:</span>
                            <span class="text-gray-900 dark:text-white font-medium">{{ formatDate(request.updated_at) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Detalles de la Solicitud -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Detalles</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Tipo de Solicitud</label>
                            <p class="mt-1 text-gray-900 dark:text-white">{{ getTypeLabel(request.request_type) }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Asunto</label>
                            <p class="mt-1 text-gray-900 dark:text-white">{{ request.title }}</p>
                        </div>

                        <div v-if="request.description">
                            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Descripción</label>
                            <p class="mt-1 text-gray-900 dark:text-white whitespace-pre-wrap break-words">
                                {{ request.description }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Información de Rechazo -->
                <div v-if="request.status === 'rechazado' && request.rejection_reason" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-red-900 dark:text-red-100 mb-2">Razón del Rechazo</h3>
                    <p class="text-red-800 dark:text-red-200">{{ request.rejection_reason }}</p>
                </div>

                <!-- Información de Aprobación -->
                <div v-if="request.status === 'aprobado' && latestApproval" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-green-900 dark:text-green-100 mb-4">Aprobado por</h3>
                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="text-green-800 dark:text-green-300 font-medium">Aprobador:</span>
                            <p class="text-green-700 dark:text-green-200">{{ latestApproval.approver_name }}</p>
                        </div>
                        <div>
                            <span class="text-green-800 dark:text-green-300 font-medium">Fecha:</span>
                            <p class="text-green-700 dark:text-green-200">{{ formatDate(latestApproval.decided_at) }}</p>
                        </div>
                        <div v-if="latestApproval.comment">
                            <span class="text-green-800 dark:text-green-300 font-medium">Comentario:</span>
                            <p class="text-green-700 dark:text-green-200">{{ latestApproval.comment }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Metadatos -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Información</h3>
                    <div class="space-y-3 text-sm">
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">ID Solicitud</p>
                            <p class="text-gray-900 dark:text-white font-mono text-xs mt-1">{{ request.id }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Estado</p>
                            <p class="text-gray-900 dark:text-white font-medium mt-1">{{ getStatusLabel(request.status) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Acciones -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Acciones</h3>
                    <div class="space-y-2">
                        <button
                            v-if="request.status === 'pendiente'"
                            @click="deleteRequest"
                            class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors text-sm"
                        >
                            Eliminar Solicitud
                        </button>
                        <p v-else class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                            No hay acciones disponibles
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';

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
    rejection_reason: string | null;
    created_at: string;
    updated_at: string;
    approvals: RequestApproval[];
}

const props = defineProps<{
    request: EmployeeRequest;
}>();

const typeLabels: Record<string, string> = {
    certificado: 'Certificado Laboral',
    permiso_especial: 'Permiso Especial',
    documento: 'Documento',
    cambio_datos: 'Cambio de Datos',
    otro: 'Otra Solicitud',
};

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
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const latestApproval = computed(() => {
    const approved = props.request.approvals.filter((a) => a.status === 'aprobado');
    return approved.length > 0 ? approved[approved.length - 1] : null;
});

const deleteRequest = () => {
    if (confirm('¿Estás seguro de que deseas eliminar esta solicitud? Esta acción no se puede deshacer.')) {
        router.delete(`/solicitudes/${props.request.id}`, {
            onSuccess: () => {
                router.visit('/solicitudes');
            },
        });
    }
};
</script>
