<template>
  <AppLayout>
    <div class="w-full px-4 py-8">
      <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-8 text-gray-900 dark:text-white">Gestión de Tipos de Ausencia / Novedades</h1>

        <!-- Resumen de duplicados -->
        <div v-if="duplicates.length > 0" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6 mb-8">
          <h2 class="text-lg font-bold text-red-900 dark:text-red-300 mb-4">⚠️ Tipos de Ausencia Duplicados</h2>
          <div class="space-y-3">
            <div v-for="dup in duplicates" :key="dup.name" class="bg-white dark:bg-gray-700 p-4 rounded border border-red-200 dark:border-red-800">
              <p class="font-semibold text-gray-900 dark:text-white">{{ dup.name }}</p>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                • {{ dup.tenant_count }} registro(s) del tenant<br>
                • {{ dup.global_count }} registro(s) global(es)
              </p>
              <p class="text-sm text-red-700 dark:text-red-300 mt-2">
                Se recomienda usar solo uno y eliminar los duplicados
              </p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-8">
          <!-- Tipos locales del tenant -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">Tipos locales ({{ types.length }})</h3>

            <form @submit.prevent="addType" class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
              <h4 class="font-semibold mb-4 text-gray-900 dark:text-white">Crear nuevo tipo</h4>
              <div class="space-y-3">
                <input v-model="newType.name" type="text" placeholder="Nombre" class="w-full border border-gray-300 dark:border-gray-600 rounded p-2 dark:bg-gray-700 dark:text-white" required />
                <label class="flex items-center gap-2">
                  <input v-model="newType.counts_as_hours" type="checkbox" />
                  <span class="text-sm text-gray-900 dark:text-gray-300">Contar como horas</span>
                </label>
                <label class="flex items-center gap-2">
                  <input v-model="newType.deducts_vacation" type="checkbox" />
                  <span class="text-sm text-gray-900 dark:text-gray-300">Descuenta vacaciones</span>
                </label>
                <button type="submit" class="w-full px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                  Agregar
                </button>
              </div>
            </form>

            <div class="space-y-2">
              <div v-for="type in types" :key="type.id" class="flex justify-between items-center p-3 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <div>
                  <p class="font-medium text-gray-900 dark:text-white">{{ type.name }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    <span v-if="type.counts_as_hours">Horas</span>
                    <span v-if="type.deducts_vacation"> • Descuenta vacaciones</span>
                  </p>
                </div>
                <button @click="deleteType(type.id)" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium">
                  Eliminar
                </button>
              </div>
            </div>
          </div>

          <!-- Tipos globales -->
          <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg shadow p-6 border border-blue-200 dark:border-blue-800">
            <h3 class="text-xl font-bold mb-6 text-blue-900 dark:text-blue-300">Tipos globales ({{ globalTypes.length }})</h3>
            <p class="text-sm text-blue-800 dark:text-blue-300 mb-6">
              Estos tipos de ausencia son estándar y disponibles para todos los tenants.
            </p>

            <div v-if="globalTypes.length === 0" class="py-8 text-center text-blue-700 dark:text-blue-300">
              No hay tipos globales
            </div>

            <div v-else class="space-y-2">
              <div v-for="type in globalTypes" :key="type.id" class="p-3 bg-white dark:bg-gray-700 rounded border border-blue-200 dark:border-blue-800">
                <p class="font-medium text-gray-900 dark:text-white">{{ type.name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                  <span v-if="type.counts_as_hours">Horas</span>
                  <span v-if="type.deducts_vacation"> • Descuenta vacaciones</span>
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabla completa de tipos -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mt-8 border border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-bold mb-4 text-gray-900 dark:text-white">Listado completo</h3>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b bg-gray-50 dark:bg-gray-700/50">
                  <th class="px-4 py-3 text-left font-medium text-gray-900 dark:text-gray-300">Nombre</th>
                  <th class="px-4 py-3 text-left font-medium text-gray-900 dark:text-gray-300">Origen</th>
                  <th class="px-4 py-3 text-center font-medium text-gray-900 dark:text-gray-300">Horas</th>
                  <th class="px-4 py-3 text-center font-medium text-gray-900 dark:text-gray-300">Descuenta Vac.</th>
                  <th class="px-4 py-3 text-center font-medium text-gray-900 dark:text-gray-300">Acción</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-for="type in [...types, ...globalTypes]" :key="type.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                  <td class="px-4 py-3 text-gray-900 dark:text-white">{{ type.name }}</td>
                  <td class="px-4 py-3">
                    <span v-if="type.tenant_id" class="px-2 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 rounded text-xs">
                      Local
                    </span>
                    <span v-else class="px-2 py-1 bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 rounded text-xs">
                      Global
                    </span>
                  </td>
                  <td class="px-4 py-3 text-center">
                    <span v-if="type.counts_as_hours" class="text-green-600 dark:text-green-400">✓</span>
                    <span v-else class="text-gray-400 dark:text-gray-600">-</span>
                  </td>
                  <td class="px-4 py-3 text-center">
                    <span v-if="type.deducts_vacation" class="text-green-600 dark:text-green-400">✓</span>
                    <span v-else class="text-gray-400 dark:text-gray-600">-</span>
                  </td>
                  <td v-if="type.tenant_id" class="px-4 py-3 text-center">
                    <button @click="deleteType(type.id)" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-xs font-medium">
                      Eliminar
                    </button>
                  </td>
                  <td v-else class="px-4 py-3 text-center text-gray-400 dark:text-gray-600">
                    Sistema
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
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { router } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const types = ref(page.props.types)
const globalTypes = ref(page.props.globalTypes)
const duplicates = ref(page.props.duplicates)

const newType = ref({ name: '', counts_as_hours: false, deducts_vacation: false })

const addType = () => {
  router.post('/admin/absence-types', newType.value, {
    onSuccess: () => { newType.value = { name: '', counts_as_hours: false, deducts_vacation: false } }
  })
}

const deleteType = (id) => {
  if (confirm('¿Estás seguro?')) {
    router.delete(`/admin/absence-types/${id}`)
  }
}
</script>
