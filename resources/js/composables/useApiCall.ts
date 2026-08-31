import axios, { AxiosError } from 'axios';
import { ref, Ref } from 'vue';
import { useNotification } from './useNotification';
import { useLoadingState } from './useLoadingState';

type HttpMethod = 'get' | 'post' | 'put' | 'patch' | 'delete';

interface ApiCallOptions {
    method?: HttpMethod;
    showError?: boolean;
    showSuccess?: boolean;
    loadingKey?: string;
    successMessage?: string;
}

export const useApiCall = () => {
    const { error: showError, success: showSuccess } = useNotification();
    const { setState, setLoading } = useLoadingState();

    const call = async <T = any>(
        url: string,
        data: any = null,
        options: ApiCallOptions = {}
    ): Promise<{ data?: T; error?: string; success: boolean }> => {
        const {
            method = data ? 'post' : 'get',
            showError: shouldShowError = true,
            showSuccess: shouldShowSuccess = false,
            loadingKey = 'loading',
            successMessage = 'Operación completada',
        } = options;

        try {
            setState(loadingKey, true);

            let response;

            if (method === 'get') {
                response = await axios.get<T>(url);
            } else if (method === 'post') {
                response = await axios.post<T>(url, data);
            } else if (method === 'put') {
                response = await axios.put<T>(url, data);
            } else if (method === 'patch') {
                response = await axios.patch<T>(url, data);
            } else if (method === 'delete') {
                response = await axios.delete<T>(url);
            }

            if (shouldShowSuccess) {
                showSuccess(successMessage);
            }

            return {
                data: response?.data,
                success: true,
            };
        } catch (err) {
            const axiosError = err as AxiosError<{ message?: string; errors?: any }>;
            const errorMessage = axiosError.response?.data?.message || 'Error en la solicitud';

            if (shouldShowError) {
                showError(errorMessage);
            }

            return {
                error: errorMessage,
                success: false,
            };
        } finally {
            setState(loadingKey, false);
        }
    };

    return {
        call,
    };
};
