<template>
  <AppLayout>
    <div class="w-full px-4 py-8">
      <div class="max-w-full">
      <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Reportes de Horas Extra</h1>
      </div>

      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-bold mb-4">Filtros</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Empleado</label>
            <select v-model="filters.user_id" @change="fetchReports" class="w-full border rounded-lg p-2">
              <option value="">Todos</option>
              <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Área</label>
            <select v-model="filters.area_id" @change="fetchReports" class="w-full border rounded-lg p-2">
              <option value="">Todas</option>
              <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Desde</label>
            <input v-model="filters.start_date" type="date" @change="fetchReports" class="w-full border rounded-lg p-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Hasta</label>
            <input v-model="filters.end_date" type="date" @change="fetchReports" class="w-full border rounded-lg p-2" />
          </div>
        </div>

        <div class="flex gap-4 mt-6">
          <button
            @click="exportExcel"
            :disabled="loading || !reports.detailed.length"
            class="px-6 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg font-medium transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Descargar Excel
          </button>
          <button
            @click="exportPdf"
            :disabled="loading || !reports.detailed.length"
            class="px-6 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-medium transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Descargar PDF
          </button>
        </div>
      </div>

      <div v-if="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>

      <div v-else class="space-y-6">
        <!-- Resumen por empleado -->
        <div class="bg-white rounded-lg shadow">
          <div class="p-6 border-b">
            <h2 class="text-xl font-bold">Resumen por Empleado</h2>
          </div>
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-sm font-semibold">Empleado</th>
                <th class="px-6 py-3 text-left text-sm font-semibold">Área</th>
                <th class="px-6 py-3 text-right text-sm font-semibold">Total Horas</th>
                <th class="px-6 py-3 text-right text-sm font-semibold">Registros</th>
                <th class="px-6 py-3 text-right text-sm font-semibold">Promedio</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <tr v-for="row in reports.by_user" :key="row.empleado" class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm">{{ row.empleado }}</td>
                <td class="px-6 py-4 text-sm">{{ row.area }}</td>
                <td class="px-6 py-4 text-sm text-right font-medium">{{ row.total_horas }}h</td>
                <td class="px-6 py-4 text-sm text-right">{{ row.total_dias }}</td>
                <td class="px-6 py-4 text-sm text-right">{{ row.promedio_horas }}h</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Resumen por área -->
        <div class="bg-white rounded-lg shadow">
          <div class="p-6 border-b">
            <h2 class="text-xl font-bold">Resumen por Área</h2>
          </div>
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
              <tr v-for="row in reports.by_area" :key="row.area" class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm">{{ row.area }}</td>
                <td class="px-6 py-4 text-sm text-right font-medium">{{ row.total_horas }}h</td>
                <td class="px-6 py-4 text-sm text-right">{{ row.total_registros }}</td>
                <td class="px-6 py-4 text-sm text-right">{{ row.promedio_horas }}h</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Detalle -->
        <div class="bg-white rounded-lg shadow">
          <div class="p-6 border-b">
            <h2 class="text-xl font-bold">Detalle de Registros</h2>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-sm font-semibold">Fecha</th>
                  <th class="px-6 py-3 text-left text-sm font-semibold">Empleado</th>
                  <th class="px-6 py-3 text-left text-sm font-semibold">Área</th>
                  <th class="px-6 py-3 text-right text-sm font-semibold">Horas</th>
                  <th class="px-6 py-3 text-left text-sm font-semibold">Estado</th>
                  <th class="px-6 py-3 text-left text-sm font-semibold">Aprobado Por</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                <tr v-for="row in reports.detailed" :key="`${row.fecha}-${row.empleado}`" class="hover:bg-gray-50">
                  <td class="px-6 py-4 text-sm">{{ row.fecha }}</td>
                  <td class="px-6 py-4 text-sm">{{ row.empleado }}</td>
                  <td class="px-6 py-4 text-sm">{{ row.area }}</td>
                  <td class="px-6 py-4 text-sm text-right font-medium">{{ row.horas }}h</td>
                  <td class="px-6 py-4 text-sm">{{ row.estado }}</td>
                  <td class="px-6 py-4 text-sm">{{ row.aprobado_por }}</td>
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

const employees = ref([])
const areas = ref([])
const loading = ref(false)
const reports = ref({
  detailed: [],
  by_user: [],
  by_area: [],
})

const filters = ref({
  user_id: '',
  area_id: '',
  start_date: '',
  end_date: '',
})

const fetchReports = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.value.user_id) params.append('user_id', filters.value.user_id)
    if (filters.value.area_id) params.append('area_id', filters.value.area_id)
    if (filters.value.start_date) params.append('start_date', filters.value.start_date)
    if (filters.value.end_date) params.append('end_date', filters.value.end_date)

    const response = await fetch(`/api/overtime-reports?${params}`)
    const data = await response.json()
    reports.value = data
  } catch (error) {
    console.error('Error fetching reports:', error)
  } finally {
    loading.value = false
  }
}

const exportExcel = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.value.user_id) params.append('user_id', filters.value.user_id)
    if (filters.value.area_id) params.append('area_id', filters.value.area_id)
    if (filters.value.start_date) params.append('start_date', filters.value.start_date)
    if (filters.value.end_date) params.append('end_date', filters.value.end_date)

    const response = await fetch(`/api/overtime-reports/export/excel?${params}`)
    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `Reporte_Horas_Extra_${new Date().toISOString().split('T')[0]}.csv`
    a.click()
  } catch (error) {
    console.error('Error exporting Excel:', error)
  } finally {
    loading.value = false
  }
}

const exportPdf = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.value.user_id) params.append('user_id', filters.value.user_id)
    if (filters.value.area_id) params.append('area_id', filters.value.area_id)
    if (filters.value.start_date) params.append('start_date', filters.value.start_date)
    if (filters.value.end_date) params.append('end_date', filters.value.end_date)

    const response = await fetch(`/api/overtime-reports/export/pdf?${params}`)
    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `Reporte_Horas_Extra_${new Date().toISOString().split('T')[0]}.html`
    a.click()
  } catch (error) {
    console.error('Error exporting PDF:', error)
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

const fetchAreas = async () => {
  try {
    const response = await fetch('/api/areas')
    const data = await response.json()
    areas.value = data
  } catch (error) {
    console.error('Error fetching areas:', error)
  }
}

onMounted(() => {
  fetchReports()
  fetchEmployees()
  fetchAreas()
})
</script>
