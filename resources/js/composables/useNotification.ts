import { useToast } from 'vue-toastification';

export const useNotification = () => {
    const toast = useToast();

    return {
        success: (message: string, options?: any) => toast.success(message, options),
        error: (message: string, options?: any) => toast.error(message, options),
        warning: (message: string, options?: any) => toast.warning(message, options),
        info: (message: string, options?: any) => toast.info(message, options),
    };
};
