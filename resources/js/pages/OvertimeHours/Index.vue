<template>
  <AppLayout>
    <div class="w-full px-4 py-8">
      <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Mis Horas Extra</h1>

        <!-- Captura de horas -->
        <div class="bg-white rounded-lg shadow p-8 mb-8">
          <h2 class="text-xl font-bold mb-6">Registrar Horas Extra</h2>

          <div v-if="errors.general" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-red-800">{{ errors.general }}</p>
          </div>

          <form @submit.prevent="addToDraft" class="space-y-6">
            <div v-if="isAdmin" class="grid gap-2">
              <label class="block text-sm font-medium text-gray-700">Registrar para</label>
              <select v-model="form.user_id" class="w-full border rounded-lg p-3">
                <option value="">Para mí</option>
                <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
              </select>
            </div>

            <div class="grid gap-2">
              <label class="block text-sm font-medium text-gray-700">Fecha</label>
              <input v-model="form.date" type="date" class="w-full border rounded-lg p-3" required />
            </div>

            <div class="grid grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Hora Inicio</label>
                <input v-model="form.start_time" type="time" @change="calculateHours" class="w-full border rounded-lg p-3" required />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Hora Fin</label>
                <input v-model="form.end_time" type="time" @change="calculateHours" class="w-full border rounded-lg p-3" required />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Horas</label>
                <div class="bg-blue-50 border border-blue-300 rounded-lg p-3 text-blue-900 font-bold text-lg">
                  {{ calculatedHours.toFixed(2) }}h
                </div>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Proyecto</label>
                <input v-model="form.project" type="text" placeholder="Código o nombre" class="w-full border rounded-lg p-3" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Cliente</label>
                <input v-model="form.client" type="text" placeholder="Nombre del cliente" class="w-full border rounded-lg p-3" />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Motivo</label>
              <textarea v-model="form.reason" placeholder="Descripción detallada" class="w-full border rounded-lg p-3 h-20 resize-none" required minlength="10"></textarea>
              <p class="text-gray-500 text-xs mt-1">Mínimo 10 caracteres</p>
            </div>

            <button type="submit" class="px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-medium">
              Añadir a la lista
            </button>
          </form>
        </div>

        <!-- Pendientes por guardar -->
        <div v-if="draftRecords.length > 0" class="bg-white rounded-lg shadow p-8 mb-8">
          <h3 class="text-lg font-bold mb-4">Pendientes ({{ draftRecords.length }})</h3>
          <div class="overflow-x-auto mb-6">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b bg-gray-50">
                  <th class="px-4 py-3 text-left">Fecha</th>
                  <th class="px-4 py-3 text-left">Horas</th>
                  <th class="px-4 py-3 text-left">Proyecto</th>
                  <th class="px-4 py-3 text-left">Motivo</th>
                  <th class="px-4 py-3 text-center">Acción</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(r, i) in draftRecords" :key="i">
                  <td class="px-4 py-3">{{ r.date }}</td>
                  <td class="px-4 py-3">{{ r.hours.toFixed(2) }}h</td>
                  <td class="px-4 py-3">{{ r.project || '-' }}</td>
                  <td class="px-4 py-3">{{ (r.reason || '').substring(0, 30) }}...</td>
                  <td class="px-4 py-3 text-center">
                    <button @click="draftRecords.splice(i, 1)" class="text-red-600 hover:text-red-800 text-xs font-medium">Quitar</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="flex gap-4">
            <button @click="saveDraft" :disabled="loading" class="flex-1 px-6 py-3 bg-green-500 text-white rounded-lg font-medium hover:bg-green-600 disabled:opacity-50">
              {{ loading ? 'Guardando...' : 'Guardar todo' }}
            </button>
            <button @click="draftRecords = []" class="flex-1 px-6 py-3 border border-gray-300 rounded-lg font-medium hover:bg-gray-50">
              Limpiar
            </button>
          </div>
        </div>

        <!-- Listado de registros -->
        <div class="bg-white rounded-lg shadow p-8">
          <h3 class="text-lg font-bold mb-6">Mis registros</h3>

          <div class="grid grid-cols-2 gap-4 mb-6">
            <select v-model="filterMonth" @change="applyFilters" class="border rounded-lg p-2">
              <option value="">Todos los meses</option>
              <option v-for="m in 12" :key="m" :value="String(m).padStart(2, '0')">
                {{ new Date(2000, m - 1).toLocaleString('es-ES', { month: 'long' }) }}
              </option>
            </select>
            <select v-model="filterYear" @change="applyFilters" class="border rounded-lg p-2">
              <option v-for="y in 5" :key="y" :value="String(new Date().getFullYear() - y + 1)">
                {{ new Date().getFullYear() - y + 1 }}
              </option>
            </select>
          </div>

          <div v-if="filteredRecords.length === 0" class="py-8 text-center text-gray-500">
            No hay registros
          </div>

          <table v-else class="w-full text-sm">
            <thead>
              <tr class="border-b bg-gray-50">
                <th class="px-4 py-3 text-left font-medium">Fecha</th>
                <th class="px-4 py-3 text-left font-medium">Horas</th>
                <th class="px-4 py-3 text-left font-medium">Proyecto</th>
                <th class="px-4 py-3 text-left font-medium">Motivo</th>
                <th class="px-4 py-3 text-center font-medium">Estado</th>
                <th class="px-4 py-3 text-center font-medium">Acción</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in filteredRecords" :key="r.id" class="border-b hover:bg-gray-50">
                <td class="px-4 py-3">{{ r.date }}</td>
                <td class="px-4 py-3 font-semibold">{{ r.hours }}h</td>
                <td class="px-4 py-3">{{ r.project || '-' }}</td>
                <td class="px-4 py-3">{{ (r.reason || '').substring(0, 30) }}...</td>
                <td class="px-4 py-3 text-center">
                  <span :class="{
                    'px-2 py-1 rounded text-xs font-medium': true,
                    'bg-yellow-100 text-yellow-800': r.status === 'pending',
                    'bg-green-100 text-green-800': r.status === 'approved',
                    'bg-red-100 text-red-800': r.status === 'rejected',
                  }">
                    {{ { pending: 'Pendiente', approved: 'Aprobado', rejected: 'Rechazado' }[r.status] }}
                  </span>
                </td>
                <td class="px-4 py-3 text-center">
                  <button v-if="r.status === 'pending'" @click="openEdit(r)" class="text-blue-600 hover:text-blue-800 text-xs font-medium">Editar</button>
                  <span v-else class="text-gray-400 text-xs">-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal edición -->
    <Teleport to="body" v-if="editingRecord">
      <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="editingRecord = null">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
          <h3 class="text-lg font-bold mb-6">Editar registro</h3>
          <form @submit.prevent="saveEdit" class="space-y-4">
            <div>
              <label class="block text-sm font-medium mb-1">Fecha</label>
              <input v-model="editForm.date" type="date" class="w-full border rounded p-2" required />
            </div>
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="block text-sm font-medium mb-1">Inicio</label>
                <input v-model="editForm.start_time" type="time" @change="calculateEditHours" class="w-full border rounded p-2" required />
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Fin</label>
                <input v-model="editForm.end_time" type="time" @change="calculateEditHours" class="w-full border rounded p-2" required />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Proyecto</label>
              <input v-model="editForm.project" type="text" class="w-full border rounded p-2" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Motivo</label>
              <textarea v-model="editForm.reason" class="w-full border rounded p-2 h-16" required></textarea>
            </div>
            <div class="flex gap-2 pt-4">
              <button type="submit" class="flex-1 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Guardar</button>
              <button type="button" @click="editingRecord = null" class="flex-1 px-4 py-2 border rounded hover:bg-gray-50">Cancelar</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { usePage, router } from '@inertiajs/vue3'

const page = usePage()
const isAdmin = computed(() => ['admin', 'superadmin'].includes(page.props.auth.user.role))

const form = ref({ user_id: '', date: new Date().toISOString().split('T')[0], start_time: '', end_time: '', project: '', client: '', reason: '' })
const editingRecord = ref(null)
const editForm = ref({ date: '', start_time: '', end_time: '', project: '', reason: '' })
const draftRecords = ref([])
const calculatedHours = ref(0)
const loading = ref(false)
const errors = ref({})
const employees = ref([])
const myRecords = ref([])
const filterMonth = ref('')
const filterYear = ref(String(new Date().getFullYear()))

const calculateHours = () => {
  if (!form.value.start_time || !form.value.end_time) { calculatedHours.value = 0; return }
  const [sh, sm] = form.value.start_time.split(':').map(Number)
  const [eh, em] = form.value.end_time.split(':').map(Number)
  calculatedHours.value = (eh * 60 + em - (sh * 60 + sm)) / 60
}

const calculateEditHours = () => {
  if (!editForm.value.start_time || !editForm.value.end_time) return
  const [sh, sm] = editForm.value.start_time.split(':').map(Number)
  const [eh, em] = editForm.value.end_time.split(':').map(Number)
}

const addToDraft = () => {
  errors.value = {}
  if (calculatedHours.value <= 0) { errors.value.general = 'Las horas deben ser > 0'; return }
  if (!form.value.reason || form.value.reason.length < 10) { errors.value.reason = 'Min 10 caracteres'; return }
  if (draftRecords.value.find(r => r.date === form.value.date)) { errors.value.general = 'Fecha duplicada'; return }

  draftRecords.value.push({ ...form.value, hours: calculatedHours.value })
  form.value = { user_id: '', date: new Date().toISOString().split('T')[0], start_time: '', end_time: '', project: '', client: '', reason: '' }
  calculatedHours.value = 0
}

const saveDraft = async () => {
  loading.value = true
  try {
    await router.post(route('overtime-hours.api.batch-store'), { records: draftRecords.value }, {
      onSuccess: () => { draftRecords.value = []; fetchRecords() },
      onError: (e) => { errors.value.general = 'Error al guardar' },
    })
  } finally { loading.value = false }
}

const fetchRecords = async () => {
  try {
    const r = await fetch(`/api/overtime-hours?start_date=${filterYear.value}-01-01&end_date=${filterYear.value}-12-31`)
    myRecords.value = await r.json()
  } catch (e) { console.error(e) }
}

const applyFilters = () => { fetchRecords() }

const filteredRecords = computed(() => myRecords.value.filter(r => !filterMonth.value || r.date.startsWith(`${filterYear.value}-${filterMonth.value}`)))

const openEdit = (record) => {
  editingRecord.value = record
  editForm.value = { date: record.date, start_time: record.start_time.substring(0, 5), end_time: record.end_time.substring(0, 5), project: record.project || '', reason: record.reason }
}

const saveEdit = async () => {
  loading.value = true
  try {
    await router.put(route('overtime-hours.api.update', editingRecord.value.id), editForm.value, { onSuccess: () => { editingRecord.value = null; fetchRecords() } })
  } finally { loading.value = false }
}

const fetchEmployees = async () => {
  if (!isAdmin.value) return
  try { employees.value = await (await fetch('/api/users/employees')).json() } catch (e) { console.error(e) }
}

onMounted(() => { fetchEmployees(); fetchRecords() })
</script>
