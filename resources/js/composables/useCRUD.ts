import axios from 'axios';
import { ref, Ref, computed } from 'vue';
import { useNotification } from './useNotification';
import { useLoadingState } from './useLoadingState';

interface CRUDOptions {
    baseUrl: string;
    showMessages?: boolean;
}

export const useCRUD = <T extends { id?: number }>(options: CRUDOptions) => {
    const { success, error: showError } = useNotification();
    const { setLoading, setSaving, setDeleting } = useLoadingState();

    const baseUrl = options.baseUrl;
    const showMessages = options.showMessages !== false;

    const items = ref<T[]>([]);
    const selectedItem = ref<T | null>(null);

    // CREATE
    const create = async (data: Partial<T>): Promise<T | null> => {
        try {
            setSaving(true);

            const response = await axios.post<T>(`${baseUrl}`, data);

            if (showMessages) {
                success('Elemento creado exitosamente');
            }

            // Agregar a la lista
            items.value.push(response.data);

            return response.data;
        } catch (err: any) {
            showError(err.response?.data?.message || 'Error al crear');
            return null;
        } finally {
            setSaving(false);
        }
    };

    // READ (Get all)
    const fetchAll = async (): Promise<T[]> => {
        try {
            setLoading(true);

            const response = await axios.get<T[]>(`${baseUrl}`);

            items.value = response.data;
            return response.data;
        } catch (err: any) {
            showError(err.response?.data?.message || 'Error al cargar');
            return [];
        } finally {
            setLoading(false);
        }
    };

    // READ (Get single)
    const fetchOne = async (id: number): Promise<T | null> => {
        try {
            setLoading(true);

            const response = await axios.get<T>(`${baseUrl}/${id}`);

            selectedItem.value = response.data;
            return response.data;
        } catch (err: any) {
            showError(err.response?.data?.message || 'Error al cargar');
            return null;
        } finally {
            setLoading(false);
        }
    };

    // UPDATE
    const update = async (id: number, data: Partial<T>): Promise<T | null> => {
        try {
            setSaving(true);

            const response = await axios.put<T>(`${baseUrl}/${id}`, data);

            if (showMessages) {
                success('Elemento actualizado exitosamente');
            }

            // Actualizar en la lista
            const index = items.value.findIndex((item) => item.id === id);
            if (index !== -1) {
                items.value[index] = response.data;
            }

            selectedItem.value = response.data;

            return response.data;
        } catch (err: any) {
            showError(err.response?.data?.message || 'Error al actualizar');
            return null;
        } finally {
            setSaving(false);
        }
    };

    // DELETE
    const deleteItem = async (id: number): Promise<boolean> => {
        try {
            setDeleting(true);

            await axios.delete(`${baseUrl}/${id}`);

            if (showMessages) {
                success('Elemento eliminado exitosamente');
            }

            // Remover de la lista
            items.value = items.value.filter((item) => item.id !== id);

            if (selectedItem.value?.id === id) {
                selectedItem.value = null;
            }

            return true;
        } catch (err: any) {
            showError(err.response?.data?.message || 'Error al eliminar');
            return false;
        } finally {
            setDeleting(false);
        }
    };

    return {
        items,
        selectedItem,
        create,
        fetchAll,
        fetchOne,
        update,
        deleteItem,
    };
};
