<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'

const toast = useToast()
const formatToDatetimeLocal = (dateStr: string) => {
    if (!dateStr) return ''

    const date = new Date(dateStr)

    const pad = (n: number) => n.toString().padStart(2, '0')

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
}
// props
const props = defineProps({
    show: Boolean,
    mode: String,
    absence: Object,
    selectedRange: Object,
    users: Array,
    isAdmin: Boolean,
})

const emit = defineEmits(['close', 'saved'])

// helpers
const getInitialForm = () => ({
    user_id: '',
    absence_type_id: '',
    start_datetime: '',
    end_datetime: '',
    reason: ''
})

const form = ref(getInitialForm())
const types = ref([])
const loading = ref(false)

// permisos
const canEdit = computed(() => {
    if (!props.absence) return true
    return props.isAdmin || props.absence.status === 'pendiente'
})

// cargar tipos
const loadTypes = async () => {
    try {
        const res = await axios.get('/absence-types')
        types.value = res.data
    } catch {
        toast.error('Error cargando tipos')
    }
}

// abrir modal
watch(() => props.show, async (val) => {
    if (!val) return

    form.value = getInitialForm()

    await loadTypes()

    // usuario normal
    if (!props.isAdmin) {
        form.value.user_id = window.Laravel?.user?.id
    }

    // crear
    if (props.mode === 'create' && props.selectedRange) {
        const start = props.selectedRange.start
        const end = new Date(props.selectedRange.end)

        end.setMinutes(end.getMinutes() - 1) // evita día extra

        form.value.start_datetime = formatToDatetimeLocal(start)
        form.value.end_datetime = formatToDatetimeLocal(end)
    }

    // ver / editar
    if (props.mode === 'view' && props.absence) {
        Object.assign(form.value, {
            user_id: props.absence.user?.id,
            absence_type_id: props.absence.type?.id,
            start_datetime: formatToDatetimeLocal(props.absence.start_datetime),
            end_datetime: formatToDatetimeLocal(props.absence.end_datetime),
            reason: props.absence.reason || ''
        })
    }

})

// validación
const validateForm = () => {
    if (!form.value.user_id) {
        toast.error('Selecciona un usuario')
        return false
    }

    if (!form.value.absence_type_id) {
        toast.error('Selecciona un tipo')
        return false
    }

    const start = new Date(form.value.start_datetime)
    const end = new Date(form.value.end_datetime)

    if (end <= start) {
        toast.error('Fechas inválidas')
        return false
    }

    return true
}

// crear
const save = async () => {
    if (!validateForm()) return

    loading.value = true

    try {
        await axios.post('/absences', form.value)

        toast.success('Ausencia creada')
        emit('saved')
        emit('close')

    } catch (e: any) {
        console.error(e)

        if (e.response?.data?.errors) {
            const errors = e.response.data.errors
            Object.values(errors).flat().forEach((msg: any) => {
                toast.error(msg)
            })
        } else {
            toast.error('Error al guardar')
        }
    } finally {
        loading.value = false
    }
}

// actualizar
const update = async () => {
    if (!validateForm()) return

    loading.value = true

    try {
        await axios.put(`/absences/${props.absence.id}`, form.value)

        toast.success('Actualizado correctamente')
        emit('saved')
        emit('close')

    } catch {
        toast.error('Error al actualizar')
    } finally {
        loading.value = false
    }
}

// aprobar
const approve = async () => {
    try {
        await axios.post(`/absences/${props.absence.id}/approve`)
        toast.success('Aprobado')
        emit('saved')
        emit('close')
    } catch {
        toast.error('Error al aprobar')
    }
}

// rechazar
const reject = async () => {
    try {
        await axios.post(`/absences/${props.absence.id}/reject`)
        toast.success('Rechazado')
        emit('saved')
        emit('close')
    } catch {
        toast.error('Error al rechazar')
    }
}
</script>

<template>
    <div v-if="show" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">

        <div class="bg-white dark:bg-gray-900 p-6 rounded-xl w-full max-w-lg shadow-xl space-y-4">

            <!-- TITLE -->
            <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                {{ mode === 'create' ? 'Nueva ausencia' : 'Detalle de ausencia' }}
            </h2>

            <!-- USER (ADMIN) -->
            <div v-if="isAdmin">
                <label class="text-sm text-gray-600 dark:text-gray-300">Usuario</label>
                <select v-model="form.user_id" class="input" :disabled="!canEdit">
                    <option value="">Seleccionar usuario</option>
                    <option v-for="u in users" :key="u.id" :value="u.id">
                        {{ u.name }}
                    </option>
                </select>
            </div>

            <!-- FORM -->
            <div class="space-y-3">

                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-300">Tipo</label>
                    <select v-model="form.absence_type_id" class="input" :disabled="!canEdit">
                        <option value="">Seleccione tipo</option>
                        <option v-for="t in types" :key="t.id" :value="t.id">
                            {{ t.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-300">Inicio</label>
                    <input type="datetime-local" v-model="form.start_datetime" class="input" :disabled="!canEdit" />
                </div>

                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-300">Fin</label>
                    <input type="datetime-local" v-model="form.end_datetime" class="input" :disabled="!canEdit" />
                </div>

                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-300">Motivo</label>
                    <textarea v-model="form.reason" class="input" :disabled="!canEdit" />
                </div>

            </div>

            <!-- ACTIONS -->
            <div class="flex justify-end gap-2 pt-2">

                <button @click="$emit('close')"
                    class="px-4 py-2 rounded bg-gray-300 dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    Cerrar
                </button>

                <!-- CREAR -->
                <button v-if="mode === 'create'" @click="save" :disabled="loading"
                    class="px-4 py-2 rounded bg-blue-600 text-white">
                    Guardar
                </button>

                <!-- EDITAR -->
                <button v-if="mode === 'view' && canEdit" @click="update" :disabled="loading"
                    class="px-4 py-2 rounded bg-yellow-500 text-white">
                    Actualizar
                </button>

                <!-- ADMIN -->
                <template v-if="mode === 'view' && isAdmin && absence?.status === 'pendiente'">

                    <button @click="reject" class="px-4 py-2 rounded bg-red-600 text-white">
                        Rechazar
                    </button>

                    <button @click="approve" class="px-4 py-2 rounded bg-green-600 text-white">
                        Aprobar
                    </button>

                </template>

            </div>

        </div>
    </div>
</template>