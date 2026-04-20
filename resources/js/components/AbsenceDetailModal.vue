<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import ConfirmDialog from './ConfirmDialog.vue';

const toast = useToast();

const props = defineProps({
    show: Boolean,
    absence: Object,
});

const emit = defineEmits(['close', 'updated']);

const loading = ref(false);

// estado visual
const statusLabel = computed(() => {
    switch (props.absence?.status) {
        case 'pendiente':
            return 'Pendiente';
        case 'aprobado':
            return 'Aprobado';
        case 'rechazado':
            return 'Rechazado';
    }
});

const statusColor = computed(() => {
    switch (props.absence?.status) {
        case 'pendiente':
            return 'bg-gray-400';
        case 'aprobado':
            return 'bg-green-500';
        case 'rechazado':
            return 'bg-red-500';
    }
});

// aprobar
const approve = async () => {
    loading.value = true

    try {
        await axios.post(`/absences/${props.absence.id}/approve`)
        toast.success('Solicitud aprobada')
        emit('updated')
        emit('close')
    } catch (e) {
        toast.error('Error al aprobar')
    } finally {
        loading.value = false
    }
}

// rechazar
const reject = async () => {
    loading.value = true

    try {
        await axios.post(`/absences/${props.absence.id}/reject`)
        toast.success('Solicitud rechazada')
        emit('updated')
        emit('close')
    } catch (e) {
        toast.error('Error al rechazar')
    } finally {
        loading.value = false
    }
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
    >
        <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-lg">
            <h2 class="mb-4 text-xl font-bold">Detalle de ausencia</h2>

            <!-- Usuario -->
            <div class="mb-4 flex items-center gap-3">
                <img
                    :src="`/storage/${absence.user.photo_path}`"
                    class="h-12 w-12 rounded-full object-cover"
                />
                <div>
                    <p class="font-semibold">{{ absence.user.name }}</p>
                    <p class="text-sm text-gray-500">
                        {{ absence.user.email }}
                    </p>
                </div>
            </div>

            <!-- Tipo -->
            <p class="mb-2"><strong>Tipo:</strong> {{ absence.type.name }}</p>

            <!-- Fechas -->
            <p class="mb-2">
                <strong>Desde:</strong> {{ absence.start_datetime }}
            </p>

            <p class="mb-2">
                <strong>Hasta:</strong> {{ absence.end_datetime }}
            </p>

            <!-- Estado -->
            <div class="mb-4">
                <span class="rounded px-3 py-1 text-white" :class="statusColor">
                    {{ statusLabel }}
                </span>
            </div>

            <!-- Motivo -->
            <p v-if="absence.reason" class="mb-4">
                <strong>Motivo:</strong> {{ absence.reason }}
            </p>

            <!-- Botones -->
            <div class="flex justify-end gap-2">
                <button
                    @click="$emit('close')"
                    class="rounded bg-gray-300 px-4 py-2"
                >
                    Cerrar
                </button>

                <!-- solo si está pendiente -->
                <template v-if="absence.status === 'pendiente'">
                    <ConfirmDialog
                        title="Rechazar solicitud"
                        description="¿Está seguro de que desea rechazar esta solicitud de ausencia?"
                        confirm-text="Rechazar"
                        cancel-text="Cancelar"
                        variant="destructive"
                        @confirm="reject"
                    >
                        <template #trigger>
                            <button
                                :disabled="loading"
                                class="rounded bg-red-600 px-4 py-2 text-white"
                            >
                                Rechazar
                            </button>
                        </template>
                    </ConfirmDialog>

                    <ConfirmDialog
                        title="Aprobar solicitud"
                        description="¿Está seguro de que desea aprobar esta solicitud de ausencia?"
                        confirm-text="Aprobar"
                        cancel-text="Cancelar"
                        variant="default"
                        @confirm="approve"
                    >
                        <template #trigger>
                            <button
                                :disabled="loading"
                                class="rounded bg-green-600 px-4 py-2 text-white"
                            >
                                Aprobar
                            </button>
                        </template>
                    </ConfirmDialog>
                </template>
            </div>
        </div>
    </div>
</template>
