import { ref, Ref, computed } from 'vue';

interface LoadingStates {
    loading?: boolean;
    saving?: boolean;
    deleting?: boolean;
    [key: string]: boolean | undefined;
}

export const useLoadingState = (initialStates: LoadingStates = {}) => {
    const states = ref<LoadingStates>({
        loading: false,
        saving: false,
        deleting: false,
        ...initialStates,
    });

    const isLoading = computed(() => states.value.loading);
    const isSaving = computed(() => states.value.saving);
    const isDeleting = computed(() => states.value.deleting);
    const isAnyLoading = computed(() => Object.values(states.value).some((v) => v === true));

    const setLoading = (value: boolean) => {
        states.value.loading = value;
    };

    const setSaving = (value: boolean) => {
        states.value.saving = value;
    };

    const setDeleting = (value: boolean) => {
        states.value.deleting = value;
    };

    const setState = (key: string, value: boolean) => {
        states.value[key] = value;
    };

    const resetStates = () => {
        Object.keys(states.value).forEach((key) => {
            states.value[key] = false;
        });
    };

    return {
        states,
        isLoading,
        isSaving,
        isDeleting,
        isAnyLoading,
        setLoading,
        setSaving,
        setDeleting,
        setState,
        resetStates,
    };
};
