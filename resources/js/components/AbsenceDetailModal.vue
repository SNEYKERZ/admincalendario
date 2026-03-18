<script setup>
import { ref, watch, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
    show: Boolean,
    absence: Object
})

const emit = defineEmits(['close', 'updated'])

const loading = ref(false)

// estado visual
const statusLabel = computed(() => {
    switch (props.absence?.status) {
        case 'pendiente': return 'Pendiente'
        case 'aprobado': return 'Aprobado'
        case 'rechazado': return 'Rechazado'
    }
})

const statusColor = computed(() => {
    switch (props.absence?.status) {
        case 'pendiente': return 'bg-gray-400'
        case 'aprobado': return 'bg-green-500'
        case 'rechazado': return 'bg-red-500'
    }
})

// aprobar
const approve = async () => {
    if (!confirm('¿Aprobar esta solicitud?')) return

    loading.value = true

    try {
        await axios.post(`/absences/${props.absence.id}/approve`)

        emit('updated')
        emit('close')

    } catch (e) {
        alert('Error al aprobar')
    } finally {
        loading.value = false
    }
}

// rechazar
const reject = async () => {
    if (!confirm('¿Rechazar esta solicitud?')) return

    loading.value = true

    try {
        await axios.post(`/absences/${props.absence.id}/reject`)

        emit('updated')
        emit('close')

    } catch (e) {
        alert('Error al rechazar')
    } finally {
        loading.value = false
    }
}
</script>

<template>
<div v-if="show" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white w-full max-w-lg p-6 rounded-xl shadow-lg">

        <h2 class="text-xl font-bold mb-4">Detalle de ausencia</h2>

        <!-- Usuario -->
        <div class="flex items-center gap-3 mb-4">
            <img
                :src="`/storage/${absence.user.photo_path}`"
                class="w-12 h-12 rounded-full object-cover"
            />
            <div>
                <p class="font-semibold">{{ absence.user.name }}</p>
                <p class="text-sm text-gray-500">{{ absence.user.email }}</p>
            </div>
        </div>

        <!-- Tipo -->
        <p class="mb-2">
            <strong>Tipo:</strong> {{ absence.type.name }}
        </p>

        <!-- Fechas -->
        <p class="mb-2">
            <strong>Desde:</strong> {{ absence.start_datetime }}
        </p>

        <p class="mb-2">
            <strong>Hasta:</strong> {{ absence.end_datetime }}
        </p>

        <!-- Estado -->
        <div class="mb-4">
            <span class="text-white px-3 py-1 rounded"
                  :class="statusColor">
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
                class="px-4 py-2 bg-gray-300 rounded"
            >
                Cerrar
            </button>

            <!-- solo si está pendiente -->
            <template v-if="absence.status === 'pendiente'">

                <button
                    @click="reject"
                    :disabled="loading"
                    class="px-4 py-2 bg-red-600 text-white rounded"
                >
                    Rechazar
                </button>

                <button
                    @click="approve"
                    :disabled="loading"
                    class="px-4 py-2 bg-green-600 text-white rounded"
                >
                    Aprobar
                </button>

            </template>

        </div>

    </div>
</div>
</template>