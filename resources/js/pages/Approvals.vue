<template>
  <Layout>
    <div class="max-w-6xl mx-auto px-4 py-8">
      <h1 class="text-3xl font-bold mb-8">Ausencias Pendientes de Aprobación</h1>

      <div v-if="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        <p class="mt-4">Cargando...</p>
      </div>

      <div v-else-if="!pendingChains.length" class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
        <p class="text-gray-600">No hay ausencias pendientes de aprobación</p>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="chain in pendingChains"
          :key="chain.id"
          class="bg-white rounded-lg shadow p-6 border-l-4"
          :style="{ borderColor: chain.absence.type.color }"
        >
          <div class="flex justify-between items-start">
            <div class="flex-1">
              <h3 class="text-xl font-semibold text-gray-800">
                {{ chain.absence.user.name }}
              </h3>
              <p class="text-sm text-gray-500 mt-1">
                Área: <span class="font-medium">{{ chain.absence.user.area?.name }}</span>
              </p>

              <div class="mt-4 grid grid-cols-2 gap-4">
                <div>
                  <p class="text-xs text-gray-500 uppercase">Tipo de Ausencia</p>
                  <p class="font-medium">{{ chain.absence.type.name }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-500 uppercase">Duración</p>
                  <p class="font-medium">{{ chain.absence.total_days }} días</p>
                </div>
                <div class="col-span-2">
                  <p class="text-xs text-gray-500 uppercase">Período</p>
                  <p class="font-medium">
                    {{ formatDate(chain.absence.start_datetime) }} a
                    {{ formatDate(chain.absence.end_datetime) }}
                  </p>
                </div>
              </div>

              <div v-if="chain.absence.internal_notes" class="mt-4 bg-blue-50 p-3 rounded">
                <p class="text-sm"><strong>Notas:</strong> {{ chain.absence.internal_notes }}</p>
              </div>
            </div>

            <div class="flex gap-2 ml-6">
              <button
                @click="openApproveModal(chain)"
                class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded font-medium transition"
              >
                ✓ Aprobar
              </button>
              <button
                @click="openRejectModal(chain)"
                class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded font-medium transition"
              >
                ✗ Rechazar
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Approve Modal -->
      <div
        v-if="showApproveModal && selectedChain"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      >
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
          <h3 class="text-xl font-bold mb-4">Aprobar Ausencia</h3>

          <div class="bg-gray-50 p-4 rounded mb-4">
            <p class="text-sm"><strong>Empleado:</strong> {{ selectedChain.absence.user.name }}</p>
            <p class="text-sm mt-1">
              <strong>Período:</strong>
              {{ formatDate(selectedChain.absence.start_datetime) }} a
              {{ formatDate(selectedChain.absence.end_datetime) }}
            </p>
            <p class="text-sm mt-1"><strong>Duración:</strong> {{ selectedChain.absence.total_days }} días</p>
          </div>

          <textarea
            v-model="approveNotes"
            placeholder="Notas adicionales (opcional)"
            class="w-full border rounded p-3 text-sm mb-4 resize-none h-24"
          ></textarea>

          <div class="flex gap-2">
            <button
              @click="showApproveModal = false"
              class="flex-1 px-4 py-2 border border-gray-300 rounded hover:bg-gray-50 transition"
            >
              Cancelar
            </button>
            <button
              @click="handleApprove"
              :disabled="approvingLoading"
              class="flex-1 px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded font-medium transition disabled:opacity-50"
            >
              {{ approvingLoading ? 'Aprobando...' : 'Aprobar' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Reject Modal -->
      <div
        v-if="showRejectModal && selectedChain"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      >
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
          <h3 class="text-xl font-bold mb-4">Rechazar Ausencia</h3>

          <div class="bg-gray-50 p-4 rounded mb-4">
            <p class="text-sm"><strong>Empleado:</strong> {{ selectedChain.absence.user.name }}</p>
            <p class="text-sm mt-1">
              <strong>Período:</strong>
              {{ formatDate(selectedChain.absence.start_datetime) }}
            </p>
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Motivo del rechazo</label>
            <select
              v-model="selectedReason"
              @change="updateRejectReason"
              class="w-full border rounded p-2 text-sm mb-2"
            >
              <option value="">-- Seleccionar motivo --</option>
              <option value="insufficient_balance">Saldo insuficiente</option>
              <option value="blackout_period">Período de bloqueo</option>
              <option value="operational_need">Necesidad operacional</option>
              <option value="other">Otro</option>
            </select>
          </div>

          <textarea
            v-model="rejectReason"
            placeholder="Motivo detallado del rechazo"
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
              :disabled="rejectingLoading || !rejectReason"
              class="flex-1 px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded font-medium transition disabled:opacity-50"
            >
              {{ rejectingLoading ? 'Rechazando...' : 'Rechazar' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import Layout from '@/layouts/AuthLayout.vue'

const pendingChains = ref([])
const loading = ref(false)
const showApproveModal = ref(false)
const showRejectModal = ref(false)
const selectedChain = ref(null)
const approveNotes = ref('')
const rejectReason = ref('')
const selectedReason = ref('')
const approvingLoading = ref(false)
const rejectingLoading = ref(false)

const fetchPendingApprovals = async () => {
  loading.value = true
  try {
    const response = await fetch('/approvals/pending')
    const data = await response.json()
    pendingChains.value = data.data || []
  } catch (error) {
    console.error('Error fetching approvals:', error)
  } finally {
    loading.value = false
  }
}

const openApproveModal = (chain) => {
  selectedChain.value = chain
  approveNotes.value = ''
  showApproveModal.value = true
}

const openRejectModal = (chain) => {
  selectedChain.value = chain
  selectedReason.value = ''
  rejectReason.value = ''
  showRejectModal.value = true
}

const handleApprove = async () => {
  if (!selectedChain.value) return

  approvingLoading.value = true
  try {
    const response = await fetch(`/approvals/${selectedChain.value.id}/approve`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ notes: approveNotes.value })
    })

    if (response.ok) {
      showApproveModal.value = false
      await fetchPendingApprovals()
    } else {
      alert('Error al aprobar la ausencia')
    }
  } catch (error) {
    console.error('Error:', error)
    alert('Error al aprobar la ausencia')
  } finally {
    approvingLoading.value = false
  }
}

const handleReject = async () => {
  if (!selectedChain.value || !rejectReason.value) return

  rejectingLoading.value = true
  try {
    const response = await fetch(`/approvals/${selectedChain.value.id}/reject`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ reason: rejectReason.value })
    })

    if (response.ok) {
      showRejectModal.value = false
      await fetchPendingApprovals()
    } else {
      alert('Error al rechazar la ausencia')
    }
  } catch (error) {
    console.error('Error:', error)
    alert('Error al rechazar la ausencia')
  } finally {
    rejectingLoading.value = false
  }
}

const updateRejectReason = () => {
  const reasons = {
    insufficient_balance: 'No tienes saldo disponible para este período',
    blackout_period: 'El período solicitado está en bloqueo operacional',
    operational_need: 'Se requiere tu presencia en operaciones durante este período',
  }
  rejectReason.value = reasons[selectedReason.value] || ''
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  })
}

onMounted(() => {
  fetchPendingApprovals()
})
</script>
