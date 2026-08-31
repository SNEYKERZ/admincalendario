import { ref, Ref } from 'vue';
import axios from 'axios';
import { useNotification } from './useNotification';

interface User {
    id: number;
    name: string;
}

interface Area {
    id: number;
    name: string;
}

interface ReportFilters {
    users: User[];
    areas: Area[];
}

// Cache global para evitar recargas
let cachedFilters: ReportFilters | null = null;
let isLoading = false;

export const useReportFilters = () => {
    const { error } = useNotification();

    const users = ref<User[]>([]);
    const areas = ref<Area[]>([]);
    const loading = ref(false);

    const load = async () => {
        // Si ya está cacheado, usar cache
        if (cachedFilters) {
            users.value = cachedFilters.users;
            areas.value = cachedFilters.areas;
            return;
        }

        // Si ya se está cargando, no hacer nada
        if (isLoading) {
            return;
        }

        isLoading = true;
        loading.value = true;

        try {
            const res = await axios.get('/reports/filters-data');

            // Validar estructura básica
            if (!Array.isArray(res.data.users) || !Array.isArray(res.data.areas)) {
                throw new Error('Estructura de datos inválida');
            }

            users.value = res.data.users;
            areas.value = res.data.areas;

            // Cachear
            cachedFilters = {
                users: res.data.users,
                areas: res.data.areas,
            };
        } catch (e) {
            console.error('Error cargando filtros:', e);
            error('Error al cargar usuarios y áreas');
        } finally {
            isLoading = false;
            loading.value = false;
        }
    };

    const refresh = async () => {
        cachedFilters = null;
        await load();
    };

    const reset = () => {
        cachedFilters = null;
        isLoading = false;
        users.value = [];
        areas.value = [];
    };

    return {
        users,
        areas,
        loading,
        load,
        refresh,
        reset,
    };
};
