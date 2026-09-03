<template>
  <AppLayout>
    <div class="w-full px-4 py-8">
      <div class="max-w-full">
      <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Gestión de Horas Extra</h1>
        <Link href="/overtime-hours/create" class="px-6 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-medium transition">
          Registrar Horas Extra
        </Link>
      </div>

      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
            <select v-model="filters.status" @change="fetchOvertimes" class="w-full border rounded-lg p-2">
              <option value="">Todos</option>
              <option value="pending">Pendiente</option>
              <option value="approved">Aprobado</option>
              <option value="rejected">Rechazado</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Empleado</label>
            <select v-model="filters.user_id" @change="fetchOvertimes" class="w-full border rounded-lg p-2">
              <option value="">Todos</option>
              <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Desde</label>
            <input v-model="filters.start_date" type="date" @change="fetchOvertimes" class="w-full border rounded-lg p-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Hasta</label>
            <input v-model="filters.end_date" type="date" @change="fetchOvertimes" class="w-full border rounded-lg p-2" />
          </div>
        </div>
      </div>

      <div v-if="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>

      <div v-else-if="!overtimes.length" class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
        <p class="text-gray-600">No hay registros de horas extra</p>
      </div>

      <div v-else class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-700">Empleado</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-700">Fecha</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-700">Hora Inicio</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-700">Hora Fin</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-700">Horas</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-700">Proyecto</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-700">Estado</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-700">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <tr v-for="overtime in overtimes" :key="overtime.id" class="hover:bg-gray-50">
                <td class="px-4 py-4">{{ overtime.user.name }}</td>
                <td class="px-4 py-4">{{ formatDate(overtime.date) }}</td>
                <td class="px-4 py-4 text-center">{{ overtime.start_time?.substring(0, 5) }}</td>
                <td class="px-4 py-4 text-center">{{ overtime.end_time?.substring(0, 5) }}</td>
                <td class="px-4 py-4 text-center font-medium">{{ overtime.hours }}h</td>
                <td class="px-4 py-4 text-xs">{{ overtime.project || '-' }}</td>
                <td class="px-4 py-4">
                  <span :class="statusClass(overtime.status)" class="px-3 py-1 rounded-full text-xs font-medium">
                    {{ statusLabel(overtime.status) }}
                  </span>
                </td>
                <td class="px-4 py-4 text-center">
                  <Link :href="`/overtime-hours/${overtime.id}`" class="text-blue-500 hover:text-blue-700 font-medium text-sm">
                    Ver
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

const overtimes = ref([])
const employees = ref([])
const loading = ref(false)
const filters = ref({
  status: '',
  user_id: '',
  start_date: '',
  end_date: '',
})

const fetchOvertimes = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.value.status) params.append('status', filters.value.status)
    if (filters.value.user_id) params.append('user_id', filters.value.user_id)
    if (filters.value.start_date) params.append('start_date', filters.value.start_date)
    if (filters.value.end_date) params.append('end_date', filters.value.end_date)

    const response = await fetch(`/api/overtime-hours?${params}`)
    const data = await response.json()
    overtimes.value = data
  } catch (error) {
    console.error('Error fetching overtimes:', error)
  } finally {
    loading.value = false
  }
}

const fetchEmployees = async () => {
  try {
    const response = await fetch('/api/users/employees')
    const data = await response.json()
    employees.value = data
  } catch (error) {
    console.error('Error fetching employees:', error)
  }
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  })
}

const statusLabel = (status) => {
  const labels = {
    pending: 'Pendiente',
    approved: 'Aprobado',
    rejected: 'Rechazado',
  }
  return labels[status] || status
}

const statusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

onMounted(() => {
  fetchOvertimes()
  fetchEmployees()
})
</script>
