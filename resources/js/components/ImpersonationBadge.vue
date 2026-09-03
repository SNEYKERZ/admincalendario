<template>
    <div v-if="isImpersonating" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg px-4 py-3 mb-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        fill-rule="evenodd"
                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                        clip-rule="evenodd"
                    />
                </svg>
                <span class="text-sm font-semibold text-yellow-800 dark:text-yellow-200">
                    Visualizando como: <strong>{{ impersonatedName }}</strong>
                </span>
            </div>
            <button
                @click="stopImpersonation"
                :disabled="loading"
                class="px-3 py-1 bg-yellow-600 text-white text-sm font-medium rounded hover:bg-yellow-700 disabled:opacity-50 transition-colors"
            >
                {{ loading ? 'Saliendo...' : 'Salir' }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const loading = ref(false);

const superAdminContext = computed(() => {
    return page.props.super_admin_context || null;
});

const isImpersonating = computed(() => {
    return superAdminContext.value?.impersonated_user_id !== null && superAdminContext.value?.impersonated_user_id !== undefined;
});

const impersonatedName = computed(() => {
    return superAdminContext.value?.impersonated_name || 'Usuario';
});

const stopImpersonation = async () => {
    loading.value = true;
    try {
        await axios.post('/superadmin/impersonate/stop');
        window.location.href = '/superadmin/dashboard';
    } catch (e) {
        console.error('Error stopping impersonation:', e);
        loading.value = false;
    }
};
</script>

