<template>
  <AppLayout>
    <div class="w-full px-4 py-8">
      <div class="max-w-full">
        <div class="flex justify-between items-center mb-8">
          <h1 class="text-3xl font-bold">Reportes de Horas Extra</h1>
          <button
            @click="exportCsv"
            :disabled="loading || !reportData.length"
            class="px-6 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg font-medium transition disabled:opacity-50"
          >
            Descargar Excel
          </button>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
          <h2 class="text-lg font-bold mb-4">Filtros</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Área</label>
              <select
                v-model="filters.area_id"
                @change="fetchReports"
                class="w-full border rounded-lg p-2"
              >
                <option value="">Todas las áreas</option>
                <option v-for="area in areas" :key="area.id" :value="area.id">
                  {{ area.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Empleado</label>
              <select
                v-model="filters.user_id"
                @change="fetchReports"
                class="w-full border rounded-lg p-2"
              >
                <option value="">Todos los empleados</option>
                <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                  {{ emp.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Desde</label>
              <input
                v-model="filters.start_date"
                type="date"
                @change="fetchReports"
                class="w-full border rounded-lg p-2"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Hasta</label>
              <input
                v-model="filters.end_date"
                type="date"
                @change="fetchReports"
                class="w-full border rounded-lg p-2"
              />
            </div>
          </div>
        </div>

        <div v-if="loading" class="text-center py-12">
          <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>

        <div v-else class="space-y-6">
          <!-- Resumen por Área -->
          <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
              <h2 class="text-xl font-bold">Resumen por Área</h2>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold">Área</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">Total Horas</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">Registros</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">Promedio</th>
                  </tr>
                </thead>
                <tbody class="divide-y">
                  <tr v-for="row in summaryByArea" :key="row.area" class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium">{{ row.area }}</td>
                    <td class="px-6 py-4 text-sm text-right font-bold">{{ row.total_horas }}h</td>
                    <td class="px-6 py-4 text-sm text-right">{{ row.total_registros }}</td>
                    <td class="px-6 py-4 text-sm text-right">{{ row.promedio_horas }}h</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Resumen por Empleado -->
          <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
              <h2 class="text-xl font-bold">Resumen por Empleado</h2>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold">Área</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold">Empleado</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">Total Horas</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">Registros</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold">Promedio</th>
                  </tr>
                </thead>
                <tbody class="divide-y">
                  <tr v-for="row in summaryByUser" :key="row.empleado" class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm">{{ row.area }}</td>
                    <td class="px-6 py-4 text-sm font-medium">{{ row.empleado }}</td>
                    <td class="px-6 py-4 text-sm text-right font-bold">{{ row.total_horas }}h</td>
                    <td class="px-6 py-4 text-sm text-right">{{ row.total_dias }}</td>
                    <td class="px-6 py-4 text-sm text-right">{{ row.promedio_horas }}h</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Detalle Completo -->
          <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
              <h2 class="text-xl font-bold">Detalle de Registros (Ordenado por Área y Empleado)</h2>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 sticky top-0">
                  <tr>
                    <th class="px-4 py-3 text-left font-semibold">Área</th>
                    <th class="px-4 py-3 text-left font-semibold">Empleado</th>
                    <th class="px-4 py-3 text-left font-semibold">Fecha</th>
                    <th class="px-4 py-3 text-center font-semibold">Hora Inicio</th>
                    <th class="px-4 py-3 text-center font-semibold">Hora Fin</th>
                    <th class="px-4 py-3 text-center font-semibold">Horas</th>
                    <th class="px-4 py-3 text-left font-semibold">Cliente</th>
                    <th class="px-4 py-3 text-left font-semibold">Proyecto</th>
                    <th class="px-4 py-3 text-left font-semibold">Estado</th>
                  </tr>
                </thead>
                <tbody class="divide-y">
                  <tr v-for="row in reportData" :key="`${row.fecha}-${row.empleado}`" class="hover:bg-gray-50">
                    <td class="px-4 py-4 font-medium text-gray-900">{{ row.area }}</td>
                    <td class="px-4 py-4">{{ row.empleado }}</td>
                    <td class="px-4 py-4">{{ row.fecha }}</td>
                    <td class="px-4 py-4 text-center">{{ row.hora_inicio }}</td>
                    <td class="px-4 py-4 text-center">{{ row.hora_fin }}</td>
                    <td class="px-4 py-4 text-center font-semibold">{{ row.tiempo_horas }}h</td>
                    <td class="px-4 py-4 text-xs">{{ row.cliente || '-' }}</td>
                    <td class="px-4 py-4 text-xs">{{ row.proyecto || '-' }}</td>
                    <td class="px-4 py-4">
                      <span
                        :class="statusClass(row.estado)"
                        class="px-2 py-1 rounded text-xs font-medium"
                      >
                        {{ statusLabel(row.estado) }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { useRoute } from 'vue-router'

const route = useRoute()

const reportData = ref([])
const summaryByUser = ref([])
const summaryByArea = ref([])
const employees = ref([])
const areas = ref([])
const loading = ref(false)

const filters = ref({
  area_id: '',
  user_id: '',
  start_date: '',
  end_date: '',
})

const fetchReports = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.value.area_id) params.append('area_id', filters.value.area_id)
    if (filters.value.user_id) params.append('user_id', filters.value.user_id)
    if (filters.value.start_date) params.append('start_date', filters.value.start_date)
    if (filters.value.end_date) params.append('end_date', filters.value.end_date)

    const response = await fetch(`/overtime-reports?${params}`)
    const data = await response.json()
    reportData.value = data.reportData || []
    summaryByUser.value = data.summaryByUser || []
    summaryByArea.value = data.summaryByArea || []
  } catch (error) {
    console.error('Error fetching reports:', error)
  } finally {
    loading.value = false
  }
}

const exportCsv = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.value.area_id) params.append('area_id', filters.value.area_id)
    if (filters.value.user_id) params.append('user_id', filters.value.user_id)
    if (filters.value.start_date) params.append('start_date', filters.value.start_date)
    if (filters.value.end_date) params.append('end_date', filters.value.end_date)

    const response = await fetch(`/overtime-reports/export?${params}`)
    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `Reporte_Horas_Extra_${new Date().toISOString().split('T')[0]}.csv`
    a.click()
  } catch (error) {
    console.error('Error exporting:', error)
  } finally {
    loading.value = false
  }
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

onMounted(async () => {
  // Fetch areas and employees from props
  const el = document.querySelector('[data-page]')
  if (el) {
    const data = JSON.parse(el.getAttribute('data-page'))
    areas.value = data.props.areas || []
    employees.value = data.props.employees || []
    reportData.value = data.props.reportData || []
    summaryByUser.value = data.props.summaryByUser || []
    summaryByArea.value = data.props.summaryByArea || []
  }
})
</script>
