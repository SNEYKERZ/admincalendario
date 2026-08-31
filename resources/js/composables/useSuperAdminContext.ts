import { computed, Ref, ref } from 'vue';
import axios from 'axios';
import { useNotification } from './useNotification';

interface SuperAdminContext {
    real_user_id: number;
    impersonated_user_id: number | null;
    tenant_id: number | null;
    effective_role: 'superadmin' | 'admin';
    started_at: string;
}

export const useSuperAdminContext = () => {
    const { success, error } = useNotification();

    const context: Ref<SuperAdminContext | null> = ref(null);
    const loading = ref(false);

    // Cargar contexto del servidor
    const loadContext = async () => {
        try {
            // El contexto viene en props de Inertia o en localStorage
            const contextData = sessionStorage.getItem('super_admin_context');
            if (contextData) {
                context.value = JSON.parse(contextData);
            }
        } catch (e) {
            console.error('Error cargando contexto:', e);
        }
    };

    // Computed properties
    const isImpersonating = computed(() => {
        return context.value?.impersonated_user_id !== null && context.value?.impersonated_user_id !== undefined;
    });

    const isGlobalContext = computed(() => {
        return !isImpersonating.value;
    });

    const tenantId = computed(() => {
        return context.value?.tenant_id;
    });

    const effectiveRole = computed(() => {
        return context.value?.effective_role;
    });

    // Iniciar impersonation
    const startImpersonation = async (userId: number) => {
        loading.value = true;

        try {
            const response = await axios.post(`/superadmin/impersonate/${userId}`);

            if (response.status === 200 || response.status === 302) {
                success('Impersonación iniciada');
                // El servidor redirige, pero actualizamos el contexto localmente
                window.location.href = '/dashboard';
            }
        } catch (e: any) {
            error(e.response?.data?.message || 'Error al iniciar impersonación');
        } finally {
            loading.value = false;
        }
    };

    // Terminar impersonation
    const stopImpersonation = async () => {
        loading.value = true;

        try {
            const response = await axios.post('/superadmin/impersonate/stop');

            if (response.status === 200 || response.status === 302) {
                success('Impersonación finalizada');
                window.location.href = '/superadmin/dashboard';
            }
        } catch (e: any) {
            error(e.response?.data?.message || 'Error al finalizar impersonación');
        } finally {
            loading.value = false;
        }
    };

    return {
        context,
        loading,
        isImpersonating,
        isGlobalContext,
        tenantId,
        effectiveRole,
        loadContext,
        startImpersonation,
        stopImpersonation,
    };
};
