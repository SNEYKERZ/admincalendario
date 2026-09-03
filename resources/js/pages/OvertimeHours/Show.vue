<template>
  <AppLayout>
    <div class="w-full px-4 py-8">
      <div class="max-w-full">
      <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Detalles de Horas Extra</h1>
        <Link href="/overtime-hours" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
          Volver
        </Link>
      </div>

      <div v-if="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>

      <div v-else-if="overtime" class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-xl font-bold mb-6">Datos del Registro (FORMATO_HE_2026.xlsx)</h2>

          <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
              <p class="text-sm text-gray-500">Empleado / ID</p>
              <p class="text-lg font-semibold">{{ overtime.user.name }} (ID: {{ overtime.user.id }})</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Área</p>
              <p class="text-lg font-semibold">{{ overtime.user.area?.name || 'No asignada' }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Fecha</p>
              <p class="text-lg font-semibold">{{ formatDate(overtime.date) }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Día / Mes</p>
              <p class="text-lg font-semibold">{{ overtime.day_of_week }}, {{ overtime.day_of_month }} de {{ overtime.month }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Hora Inicio</p>
              <p class="text-lg font-semibold">{{ overtime.start_time?.substring(0, 5) }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Hora Fin</p>
              <p class="text-lg font-semibold">{{ overtime.end_time?.substring(0, 5) }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Cliente</p>
              <p class="text-lg font-semibold">{{ overtime.client || 'N/A' }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Proyecto</p>
              <p class="text-lg font-semibold">{{ overtime.project || 'N/A' }}</p>
            </div>
            <div class="col-span-2">
              <p class="text-sm text-gray-500">Tiempo (horas)</p>
              <p class="text-2xl font-bold text-blue-600">{{ overtime.hours }}h</p>
            </div>
            <div class="col-span-2">
              <p class="text-sm text-gray-500">Motivo / Justificación</p>
              <p class="text-gray-700 mt-2 bg-gray-50 p-3 rounded">{{ overtime.reason }}</p>
            </div>
          </div>

          <div class="border-t pt-6 mt-6 grid grid-cols-2 gap-6">
            <div>
              <p class="text-sm text-gray-500">Estado</p>
              <span :class="statusClass(overtime.status)" class="inline-block px-3 py-1 rounded-full text-sm font-medium mt-2">
                {{ statusLabel(overtime.status) }}
              </span>
            </div>
            <div v-if="overtime.approved_at">
              <p class="text-sm text-gray-500">Aprobado por</p>
              <p class="text-lg font-semibold">{{ overtime.approver?.name || 'Sistema' }}</p>
            </div>
          </div>

          <div v-if="overtime.approval_notes" class="mt-6 pt-6 border-t">
            <p class="text-sm text-gray-500 mb-2">Notas de aprobación</p>
            <p class="text-gray-700 bg-yellow-50 p-3 rounded">{{ overtime.approval_notes }}</p>
          </div>
        </div>

        <div v-if="overtime.status === 'pending' && isAdmin" class="bg-white rounded-lg shadow p-6">
          <h2 class="text-xl font-bold mb-4">Acciones</h2>
          <div class="flex gap-4">
            <button
              @click="showApproveModal = true"
              class="px-6 py-3 bg-green-500 hover:bg-green-600 text-white rounded-lg font-medium transition"
            >
              Aprobar
            </button>
            <button
              @click="showRejectModal = true"
              class="px-6 py-3 bg-red-500 hover:bg-red-600 text-white rounded-lg font-medium transition"
            >
              Rechazar
            </button>
          </div>
        </div>

        <div v-if="overtime.status === 'pending' && !isAdmin" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
          <p class="text-yellow-800">Este registro está pendiente de aprobación por un administrador</p>
        </div>
      </div>

      <!-- Approve Modal -->
      <div
        v-if="showApproveModal && overtime"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      >
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
          <h3 class="text-xl font-bold mb-4">Aprobar Horas Extra</h3>

          <div class="bg-gray-50 p-4 rounded mb-4">
            <p class="text-sm"><strong>Empleado:</strong> {{ overtime.user.name }}</p>
            <p class="text-sm mt-1"><strong>Fecha:</strong> {{ formatDate(overtime.date) }}</p>
            <p class="text-sm mt-1"><strong>Horas:</strong> {{ overtime.hours }}</p>
          </div>

          <div class="flex gap-2">
            <button
              @click="showApproveModal = false"
              class="flex-1 px-4 py-2 border border-gray-300 rounded hover:bg-gray-50 transition"
            >
              Cancelar
            </button>
            <button
              @click="handleApprove"
              :disabled="approveLoading"
              class="flex-1 px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded font-medium transition disabled:opacity-50"
            >
              {{ approveLoading ? 'Aprobando...' : 'Aprobar' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Reject Modal -->
      <div
        v-if="showRejectModal && overtime"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      >
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
          <h3 class="text-xl font-bold mb-4">Rechazar Horas Extra</h3>

          <textarea
            v-model="rejectReason"
            placeholder="Motivo del rechazo"
            class="w-full border rounded p-3 text-sm mb-4 resize-none h-24"
          ></textarea>

          <div class="flex gap-2">
            <button
              @click="showRejectModal = false"
              class="flex-1 px-4 py-2 border border-gray-300 rounded hover:bg-gray-50 transition"
            >
              Cancelar
            </button>
            <button
              @click="handleReject"
              :disabled="rejectLoading || !rejectReason"
              class="flex-1 px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded font-medium transition disabled:opacity-50"
            >
              {{ rejectLoading ? 'Rechazando...' : 'Rechazar' }}
            </button>
          </div>
        </div>
      </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const route = useRoute()
const isAdmin = computed(() => page.props.auth.user.role === 'admin')

const overtime = ref(null)
const loading = ref(false)
const showApproveModal = ref(false)
const showRejectModal = ref(false)
const rejectReason = ref('')
const approveLoading = ref(false)
const rejectLoading = ref(false)

const fetchOvertime = async () => {
  loading.value = true
  try {
    const id = route.params.id
    const response = await fetch(`/api/overtime-hours/${id}`)
    const data = await response.json()
    overtime.value = data
  } catch (error) {
    console.error('Error fetching overtime:', error)
  } finally {
    loading.value = false
  }
}

const handleApprove = async () => {
  approveLoading.value = true
  try {
    const response = await fetch(`/api/overtime-hours/${overtime.value.id}/approve`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
    })

    if (response.ok) {
      showApproveModal.value = false
      await fetchOvertime()
    } else {
      alert('Error al aprobar')
    }
  } catch (error) {
    console.error('Error:', error)
    alert('Error al aprobar')
  } finally {
    approveLoading.value = false
  }
}

const handleReject = async () => {
  rejectLoading.value = true
  try {
    const response = await fetch(`/api/overtime-hours/${overtime.value.id}/reject`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ reason: rejectReason.value }),
    })

    if (response.ok) {
      showRejectModal.value = false
      await fetchOvertime()
    } else {
      alert('Error al rechazar')
    }
  } catch (error) {
    console.error('Error:', error)
    alert('Error al rechazar')
  } finally {
    rejectLoading.value = false
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

const useRoute = () => {
  return {
    params: { id: window.location.pathname.split('/').pop() },
  }
}

onMounted(() => {
  fetchOvertime()
})
</script>
