import { useToast } from 'vue-toastification'

export const useAppToast = () => {
    const toast = useToast()

    return {
        success: (msg: string) => toast.success(msg),
        error: (msg: string) => toast.error(msg),
        info: (msg: string) => toast.info(msg),
    }
}

