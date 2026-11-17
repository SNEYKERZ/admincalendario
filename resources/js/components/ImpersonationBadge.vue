<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { Eye } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useNotification } from '@/composables/useNotification';

const page = usePage();
const loading = ref(false);
const notify = useNotification();

const superAdminContext = computed<any>(
    () => (page.props as any).super_admin_context || null,
);

const isImpersonating = computed(
    () =>
        superAdminContext.value?.impersonated_user_id !== null &&
        superAdminContext.value?.impersonated_user_id !== undefined,
);

const impersonatedName = computed(
    () => superAdminContext.value?.impersonated_name || 'Usuario',
);

const stopImpersonation = async () => {
    loading.value = true;
    try {
        await axios.post('/superadmin/impersonate/stop');
        window.location.href = '/superadmin/dashboard';
    } catch (e) {
        console.error('Error stopping impersonation:', e);
        notify.error('No se pudo salir de la suplantación. Inténtalo de nuevo.');
        loading.value = false;
    }
};
</script>

<template>
    <div
        v-if="isImpersonating"
        role="status"
        class="flex flex-wrap items-center justify-between gap-2 border-b border-warning/25 bg-warning-soft px-4 py-2 text-sm text-warning"
    >
        <span class="flex items-center gap-2">
            <Eye class="size-4" aria-hidden="true" />
            Estás viendo la aplicación como
            <strong class="font-semibold">{{ impersonatedName }}</strong>
        </span>
        <Button
            size="sm"
            variant="outline"
            class="h-7 border-warning/40 bg-transparent text-warning hover:bg-warning/10 hover:text-warning"
            :disabled="loading"
            @click="stopImpersonation"
        >
            <Spinner v-if="loading" />
            {{ loading ? 'Saliendo…' : 'Salir de la suplantación' }}
        </Button>
    </div>
</template>
