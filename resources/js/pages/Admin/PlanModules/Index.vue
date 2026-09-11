<template>
  <AppLayout>
    <div class="w-full px-4 py-8">
      <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-bold mb-8 text-gray-900 dark:text-white">Gestión de Planes y Módulos</h1>

        <div class="space-y-8">
          <div v-for="plan in plans" :key="plan.id" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 border-l-4 border-blue-500">
            <div class="flex justify-between items-start mb-6">
              <div>
                <h2 class="text-2xl font-bold mb-2 text-gray-900 dark:text-white">{{ plan.name }}</h2>
                <p class="text-gray-600 dark:text-gray-400">{{ plan.description }}</p>
              </div>
              <div class="text-right">
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">${{ (plan.price_usd || 0).toLocaleString() }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ plan.duration_days }} días</p>
                <p v-if="plan.discount_percentage" class="text-sm text-green-600 dark:text-green-400">Descuento: {{ plan.discount_percentage }}%</p>
              </div>
            </div>

            <form @submit.prevent="updatePlan(plan)">
              <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Módulos incluidos</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  <label v-for="module in allModules" :key="module.id" class="flex items-center gap-3 p-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-blue-50 dark:hover:bg-gray-700/50 cursor-pointer dark:bg-gray-700/30">
                    <input
                      type="checkbox"
                      :value="module.id"
                      v-model="plan.module_ids"
                      class="w-4 h-4 rounded"
                    />
                    <span>
                      <p class="font-medium text-gray-900 dark:text-white">{{ module.name }}</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">{{ module.slug }}</p>
                    </span>
                  </label>
                </div>
              </div>

              <div class="flex gap-3">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                  Guardar cambios
                </button>
              </div>
            </form>

            <div v-if="plan.modules.length > 0" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-2"><strong>Módulos activos:</strong></p>
              <div class="flex flex-wrap gap-2">
                <span v-for="module in plan.modules" :key="module.id" class="px-3 py-1 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 rounded-full text-sm">
                  {{ module.name }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { usePage, router } from '@inertiajs/vue3'

const page = usePage()
const plans = ref(page.props.plans)
const allModules = ref(page.props.allModules)

const updatePlan = (plan) => {
  router.put(`/admin/gestion-sistema/planes/${plan.id}`, {
    name: plan.name,
    price_cop: plan.price_cop,
    price_usd: plan.price_usd,
    discount_percentage: plan.discount_percentage,
    duration_days: plan.duration_days,
    description: plan.description,
    module_ids: plan.module_ids,
  }, {
    onError: (errors) => {
      console.error(errors)
      alert('Error al guardar: ' + JSON.stringify(errors))
    }
  })
}
</script>
