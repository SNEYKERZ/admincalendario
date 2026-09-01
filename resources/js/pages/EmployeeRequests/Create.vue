<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link href="/solicitudes" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 font-medium">
                    ← Volver
                </Link>
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Nueva Solicitud</h2>
                    <p class="text-gray-500 dark:text-gray-400 mt-2">Completa el formulario para enviar tu solicitud</p>
                </div>
            </div>
        </template>

        <div class="max-w-2xl">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Tipo de Solicitud -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Tipo de Solicitud *
                    </label>
                    <select
                        v-model="form.request_type"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                        <option value="">Selecciona un tipo...</option>
                        <option v-for="(label, key) in requestTypes" :key="key" :value="key">
                            {{ label }}
                        </option>
                    </select>
                    <p v-if="errors.request_type" class="mt-1 text-sm text-red-600">{{ errors.request_type }}</p>
                </div>

                <!-- Asunto -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Asunto *
                    </label>
                    <input
                        v-model="form.title"
                        type="text"
                        placeholder="Ej: Certificado laboral para banco"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                        maxlength="255"
                    />
                    <p v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title }}</p>
                </div>

                <!-- Descripción -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Descripción
                    </label>
                    <textarea
                        v-model="form.description"
                        placeholder="Proporciona más detalles sobre tu solicitud (opcional)"
                        rows="5"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        maxlength="1000"
                    />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ form.description?.length || 0 }} / 1000 caracteres
                    </p>
                    <p v-if="errors.description" class="mt-1 text-sm text-red-600">{{ errors.description }}</p>
                </div>

                <!-- Botones -->
                <div class="flex gap-4">
                    <button
                        type="submit"
                        :disabled="loading"
                        class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50"
                    >
                        {{ loading ? 'Enviando...' : 'Enviar Solicitud' }}
                    </button>
                    <Link
                        href="/solicitudes"
                        class="flex-1 px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors text-center"
                    >
                        Cancelar
                    </Link>
                </div>
            </form>

            <!-- Información -->
            <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <p class="text-sm text-blue-900 dark:text-blue-100">
                    ℹ️ Tu solicitud será revisada por el equipo de Recursos Humanos. Recibirás una notificación cuando sea procesada.
                </p>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';

interface FormData {
    request_type: string;
    title: string;
    description: string | null;
}

interface Errors {
    [key: string]: string;
}

const requestTypes = {
    certificado: 'Certificado Laboral',
    permiso_especial: 'Permiso Especial',
    documento: 'Documento',
    cambio_datos: 'Cambio de Datos',
    otro: 'Otra Solicitud',
};

const form = reactive<FormData>({
    request_type: '',
    title: '',
    description: null,
});

const errors = ref<Errors>({});
const loading = ref(false);

const submit = async () => {
    loading.value = true;
    errors.value = {};

    router.post('/solicitudes', form, {
        onError: (err) => {
            errors.value = err as Errors;
            loading.value = false;
        },
        onSuccess: () => {
            loading.value = false;
        },
    });
};
</script>
