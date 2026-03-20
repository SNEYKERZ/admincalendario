<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import AbsenceModal from './AbsenceModal.vue'
import UserFilter from './UserFilter.vue'
import { useToast } from 'vue-toastification'
import tippy from 'tippy.js'
import 'tippy.js/dist/tippy.css'

const toast = useToast()

const users = ref([])
const loading = ref(false)

const loadUsers = async () => {
    loading.value = true
    try {
        const res = await axios.get('/gestion-usuarios/data')
        users.value = res.data
    } catch {
        toast.error('Error cargando usuarios')
    } finally {
        loading.value = false
    }
}

const adjustDays = async (user, days) => {
    try {
        await axios.post(`/gestion-usuarios/${user.id}/adjust`, {
            days
        })

        toast.success('Días actualizados')
        loadUsers()

    } catch {
        toast.error('Error actualizando')
    }
}

onMounted(loadUsers)
</script>

<template>
<div class="p-4 space-y-4">

    <h1 class="text-xl font-bold">Gestión de usuarios</h1>

    <div class="bg-white dark:bg-gray-900 rounded-xl shadow overflow-hidden">

        <table class="w-full text-sm">
            <thead class="bg-gray-100 dark:bg-gray-800">
                <tr>
                    <th class="p-3 text-left">Usuario</th>
                    <th>Asignados</th>
                    <th>Usados</th>
                    <th>Disponibles</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                <tr v-for="u in users" :key="u.id" class="border-t">

                    <td class="p-3">
                        <div class="font-medium">{{ u.name }}</div>
                        <div class="text-xs text-gray-500">{{ u.email }}</div>
                    </td>

                    <td class="text-center">{{ u.allocated }}</td>
                    <td class="text-center">{{ u.used }}</td>
                    <td class="text-center font-bold">{{ u.available }}</td>

                    <td class="flex gap-2 justify-center p-2">

                        <button
                            @click="adjustDays(u, 1)"
                            class="px-2 py-1 bg-green-600 text-white rounded">
                            +1
                        </button>

                        <button
                            @click="adjustDays(u, -1)"
                            class="px-2 py-1 bg-red-600 text-white rounded">
                            -1
                        </button>

                    </td>

                </tr>
            </tbody>
        </table>

    </div>

</div>
</template>
