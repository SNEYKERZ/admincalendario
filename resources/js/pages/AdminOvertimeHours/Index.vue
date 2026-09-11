<template>
  <AppLayout>
    <div class="w-full px-4 py-8">
      <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-8">
          <h1 class="text-3xl font-bold">Administración Horas Extra</h1>
          <a :href="exportUrl" class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 font-medium">
            Exportar Excel
          </a>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
          <div class="grid grid-cols-4 gap-4">
            <select v-model="filters.area_id" @change="applyFilters" class="border rounded-lg p-3">
              <option value="">Todas las áreas</option>
              <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
            </select>
            <select v-model="filters.user_id" @change="applyFilters" class="border rounded-lg p-3">
              <option value="">Todos los empleados</option>
              <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
            </select>
            <select v-model="filters.month" @change="applyFilters" class="border rounded-lg p-3">
              <option value="">Todos los meses</option>
              <option v-for="m in 12" :key="m" :value="String(m).padStart(2, '0')">
                {{ new Date(2000, m - 1).toLocaleString('es-ES', { month: 'long' }) }}
              </option>
            </select>
            <select v-model="filters.year" @change="applyFilters" class="border rounded-lg p-3">
              <option v-for="y in 5" :key="y" :value="String(new Date().getFullYear() - y + 1)">
                {{ new Date().getFullYear() - y + 1 }}
              </option>
            </select>
          </div>
        </div>

        <!-- Tabla -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b bg-gray-50">
                  <th class="px-4 py-3 text-left font-medium">Empleado</th>
                  <th class="px-4 py-3 text-left font-medium">Área</th>
                  <th class="px-4 py-3 text-left font-medium">Fecha</th>
                  <th class="px-4 py-3 text-left font-medium">Horas</th>
                  <th class="px-4 py-3 text-left font-medium">Proyecto</th>
                  <th class="px-4 py-3 text-left font-medium">Motivo</th>
                  <th class="px-4 py-3 text-center font-medium">Estado</th>
                  <th class="px-4 py-3 text-center font-medium">Acción</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="ot in overtimes.data" :key="ot.id" class="border-b hover:bg-gray-50">
                  <td class="px-4 py-3">{{ ot.user.name }}</td>
                  <td class="px-4 py-3">{{ ot.user.area?.name || '-' }}</td>
                  <td class="px-4 py-3">{{ ot.date }}</td>
                  <td class="px-4 py-3 font-semibold">{{ ot.hours }}h</td>
                  <td class="px-4 py-3">{{ ot.project || '-' }}</td>
                  <td class="px-4 py-3">{{ (ot.reason || '').substring(0, 20) }}...</td>
                  <td class="px-4 py-3 text-center">
                    <span :class="{
                      'px-2 py-1 rounded text-xs font-medium': true,
                      'bg-yellow-100 text-yellow-800': ot.status === 'pending',
                      'bg-green-100 text-green-800': ot.status === 'approved',
                      'bg-red-100 text-red-800': ot.status === 'rejected',
                    }">
                      {{ { pending: 'Pendiente', approved: 'Aprobado', rejected: 'Rechazado' }[ot.status] }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-center">
                    <div class="flex gap-2 justify-center">
                      <button @click="openEdit(ot)" class="text-blue-600 hover:text-blue-800 text-xs font-medium">Editar</button>
                      <button v-if="ot.status === 'pending'" @click="openApprove(ot)" class="text-green-600 hover:text-green-800 text-xs font-medium">Aprobar</button>
                      <button v-if="ot.status === 'pending'" @click="openReject(ot)" class="text-red-600 hover:text-red-800 text-xs font-medium">Rechazar</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Paginación simple -->
          <div v-if="overtimes.last_page > 1" class="mt-6 flex justify-between items-center">
            <span class="text-sm text-gray-600">Página {{ overtimes.current_page }} de {{ overtimes.last_page }}</span>
            <div class="flex gap-2">
              <button v-if="overtimes.current_page > 1" @click="goToPage(overtimes.current_page - 1)" class="px-4 py-2 border rounded hover:bg-gray-50">Anterior</button>
              <button v-if="overtimes.current_page < overtimes.last_page" @click="goToPage(overtimes.current_page + 1)" class="px-4 py-2 border rounded hover:bg-gray-50">Siguiente</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Editar -->
    <Teleport to="body" v-if="editingOT">
      <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="editingOT = null">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
          <h3 class="text-lg font-bold mb-6">Editar Registro</h3>
          <form @submit.prevent="saveEdit" class="space-y-4">
            <input v-model="editForm.date" type="date" class="w-full border rounded p-2" required />
            <div class="grid grid-cols-2 gap-2">
              <input v-model="editForm.start_time" type="time" class="border rounded p-2" required />
              <input v-model="editForm.end_time" type="time" class="border rounded p-2" required />
            </div>
            <input v-model="editForm.project" type="text" placeholder="Proyecto" class="w-full border rounded p-2" />
            <textarea v-model="editForm.reason" placeholder="Motivo" class="w-full border rounded p-2 h-16" required></textarea>
            <div class="flex gap-2 pt-4">
              <button type="submit" class="flex-1 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Guardar</button>
              <button type="button" @click="editingOT = null" class="flex-1 px-4 py-2 border rounded">Cancelar</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Modal Aprobar -->
    <Teleport to="body" v-if="approvingOT">
      <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="approvingOT = null">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
          <h3 class="text-lg font-bold mb-6">Aprobar Registro</h3>
          <p class="text-gray-700 mb-6">¿Deseas aprobar este registro?</p>
          <div class="flex gap-2">
            <button @click="approve" class="flex-1 px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">Sí, Aprobar</button>
            <button @click="approvingOT = null" class="flex-1 px-4 py-2 border rounded">Cancelar</button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal Rechazar -->
    <Teleport to="body" v-if="rejectingOT">
      <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="rejectingOT = null">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
          <h3 class="text-lg font-bold mb-6">Rechazar Registro</h3>
          <form @submit.prevent="reject" class="space-y-4">
            <textarea v-model="rejectReason" placeholder="Motivo del rechazo" class="w-full border rounded p-2 h-16" required></textarea>
            <div class="flex gap-2">
              <button type="submit" class="flex-1 px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">Rechazar</button>
              <button type="button" @click="rejectingOT = null" class="flex-1 px-4 py-2 border rounded">Cancelar</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { usePage, router } from '@inertiajs/vue3'

const page = usePage()
const overtimes = ref(page.props.overtimes)
const areas = ref(page.props.areas)
const employees = ref(page.props.employees)

const filters = ref(page.props.filters)
const editingOT = ref(null)
const editForm = ref({ date: '', start_time: '', end_time: '', project: '', reason: '' })
const approvingOT = ref(null)
const rejectingOT = ref(null)
const rejectReason = ref('')

const exportUrl = computed(() => {
  const params = new URLSearchParams(filters.value)
  return route('admin.overtime-hours.export', Object.fromEntries(params))
})

const applyFilters = () => {
  router.get(route('admin.overtime-hours.index'), filters.value, { preserveState: true })
}

const goToPage = (page) => {
  router.get(route('admin.overtime-hours.index'), { ...filters.value, page }, { preserveState: true })
}

const openEdit = (ot) => {
  editingOT.value = ot
  editForm.value = { date: ot.date, start_time: ot.start_time.substring(0, 5), end_time: ot.end_time.substring(0, 5), project: ot.project || '', reason: ot.reason }
}

const saveEdit = async () => {
  router.put(route('admin.overtime-hours.update', editingOT.value.id), editForm.value, {
    onSuccess: () => { editingOT.value = null }
  })
}

const openApprove = (ot) => { approvingOT.value = ot }

const approve = () => {
  router.post(route('admin.overtime-hours.approve', approvingOT.value.id), {}, {
    onSuccess: () => { approvingOT.value = null }
  })
}

const openReject = (ot) => { rejectingOT.value = ot; rejectReason.value = '' }

const reject = () => {
  router.post(route('admin.overtime-hours.reject', rejectingOT.value.id), { reason: rejectReason.value }, {
    onSuccess: () => { rejectingOT.value = null }
  })
}
</script>
