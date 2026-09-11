<template>
  <AppLayout>
    <div class="w-full px-4 py-8">
      <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Registrar Horas Extra</h1>

      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <p class="text-sm text-blue-800">
          <strong>Formato oficial FORMATO_HE_2026.xlsx</strong><br>
          Límites legales: Máximo 2 horas diarias y 12 horas semanales (Ley 2101)
        </p>
      </div>

      <form @submit.prevent="submit" class="bg-white rounded-lg shadow p-8">
        <div v-if="errors.general" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
          <p class="text-red-800">{{ errors.general }}</p>
        </div>

        <!-- Empleado (autocompleta con usuario autenticado) -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Empleado</label>
          <div class="bg-gray-50 border rounded-lg p-3 text-gray-700">
            {{ currentUser.name }} (ID: {{ currentUser.id }})
          </div>
        </div>

        <!-- Admin puede seleccionar otro empleado -->
        <div v-if="isAdmin" class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Registrar para (opcional)</label>
          <select v-model="form.user_id" class="w-full border rounded-lg p-3">
            <option value="">Yo mismo</option>
            <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
          </select>
          <p v-if="errors.user_id" class="text-red-500 text-sm mt-1">{{ errors.user_id }}</p>
        </div>

        <!-- Fecha con extracción automática -->
        <div class="mb-6 grid grid-cols-1 lg:grid-cols-4 gap-4">
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha</label>
            <input
              v-model="form.date"
              type="date"
              @change="updateDateComponents"
              class="w-full border rounded-lg p-3"
              required
            />
            <p v-if="errors.date" class="text-red-500 text-sm mt-1">{{ errors.date }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Día Semana</label>
            <div class="bg-gray-50 border rounded-lg p-3 text-gray-700 text-sm">
              {{ dateComponents.dayOfWeek }}
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Mes</label>
            <div class="bg-gray-50 border rounded-lg p-3 text-gray-700 text-sm">
              {{ dateComponents.month }}
            </div>
          </div>
        </div>

        <!-- Hora Inicio y Fin -->
        <div class="mb-6 grid grid-cols-1 lg:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Hora Inicio</label>
            <input
              v-model="form.start_time"
              type="time"
              @change="calculateHours"
              class="w-full border rounded-lg p-3"
              required
            />
            <p v-if="errors.start_time" class="text-red-500 text-sm mt-1">{{ errors.start_time }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Hora Fin</label>
            <input
              v-model="form.end_time"
              type="time"
              @change="calculateHours"
              class="w-full border rounded-lg p-3"
              required
            />
            <p v-if="errors.end_time" class="text-red-500 text-sm mt-1">{{ errors.end_time }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tiempo (horas)</label>
            <div class="bg-blue-50 border border-blue-300 rounded-lg p-3 text-blue-900 font-bold text-lg">
              {{ calculatedHours.toFixed(2) }}h
            </div>
          </div>
        </div>

        <!-- Cliente y Proyecto -->
        <div class="mb-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Cliente</label>
            <input
              v-model="form.client"
              type="text"
              placeholder="Nombre del cliente o entidad"
              class="w-full border rounded-lg p-3"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Proyecto / Código</label>
            <input
              v-model="form.project"
              type="text"
              placeholder="Código o nombre del proyecto"
              class="w-full border rounded-lg p-3"
            />
          </div>
        </div>

        <!-- Motivo -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Motivo / Justificación</label>
          <textarea
            v-model="form.reason"
            placeholder="Descripción detallada de la labor realizada"
            class="w-full border rounded-lg p-3 h-24 resize-none"
            required
            minlength="10"
          ></textarea>
          <p class="text-gray-500 text-xs mt-1">Mínimo 10 caracteres</p>
          <p v-if="errors.reason" class="text-red-500 text-sm mt-1">{{ errors.reason }}</p>
        </div>

        <div class="flex gap-4">
          <button
            type="submit"
            :disabled="loading"
            class="flex-1 px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-medium transition disabled:opacity-50"
          >
            {{ loading ? 'Guardando...' : 'Guardar Registro' }}
          </button>
          <Link href="/overtime-hours" class="flex-1 px-6 py-3 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition text-center">
            Cancelar
          </Link>
        </div>
      </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const isAdmin = computed(() => page.props.auth.user.role === 'admin')

const currentUser = computed(() => page.props.auth.user)

const form = ref({
  user_id: '',
  date: '',
  start_time: '',
  end_time: '',
  client: '',
  project: '',
  reason: '',
})

const dateComponents = ref({
  dayOfWeek: '',
  month: '',
})

const calculatedHours = ref(0)
const employees = ref([])
const loading = ref(false)
const errors = ref({})

const fetchEmployees = async () => {
  if (!isAdmin.value) return

  try {
    const response = await fetch('/api/users/employees')
    const data = await response.json()
    employees.value = data
  } catch (error) {
    console.error('Error fetching employees:', error)
  }
}

const updateDateComponents = () => {
  if (!form.value.date) return

  const date = new Date(form.value.date)
  const dayOfWeek = new Intl.DateTimeFormat('es-ES', { weekday: 'long' }).format(date)
  const month = new Intl.DateTimeFormat('es-ES', { month: 'long' }).format(date)

  dateComponents.value.dayOfWeek = dayOfWeek.charAt(0).toUpperCase() + dayOfWeek.slice(1)
  dateComponents.value.month = month.charAt(0).toUpperCase() + month.slice(1)
}

const calculateHours = () => {
  if (!form.value.start_time || !form.value.end_time) {
    calculatedHours.value = 0
    return
  }

  const [startHour, startMin] = form.value.start_time.split(':').map(Number)
  const [endHour, endMin] = form.value.end_time.split(':').map(Number)

  const startTotalMin = startHour * 60 + startMin
  const endTotalMin = endHour * 60 + endMin

  if (endTotalMin <= startTotalMin) {
    calculatedHours.value = 0
    return
  }

  calculatedHours.value = (endTotalMin - startTotalMin) / 60
}

const getCsrfToken = () => {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
  if (!token) {
    const csrfElement = document.querySelector('input[name="_token"]')
    return csrfElement?.value || ''
  }
  return token
}

const submit = async () => {
  loading.value = true
  errors.value = {}

  try {
    const data = {
      date: form.value.date,
      start_time: form.value.start_time,
      end_time: form.value.end_time,
      client: form.value.client,
      project: form.value.project,
      reason: form.value.reason,
    }

    if (isAdmin.value && form.value.user_id) {
      data.user_id = form.value.user_id
    }

    const response = await fetch('/api/overtime-hours', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': getCsrfToken(),
        'Accept': 'application/json',
      },
      body: JSON.stringify(data),
    })

    if (response.ok) {
      window.location.href = '/overtime-hours'
    } else {
      const errorData = await response.json()
      if (errorData.errors) {
        errors.value = errorData.errors
      } else {
        errors.value.general = errorData.message || 'Error al guardar'
      }
    }
  } catch (error) {
    console.error('Error:', error)
    errors.value.general = 'Error al guardar el registro'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchEmployees()
  form.value.date = new Date().toISOString().split('T')[0]
  updateDateComponents()
})
</script>
