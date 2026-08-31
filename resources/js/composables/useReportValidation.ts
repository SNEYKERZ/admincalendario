import { computed, Ref } from 'vue';

interface ValidationOptions {
    reportType: Ref<'personal' | 'area'>;
    selectedUser: Ref<string | number>;
    selectedArea: Ref<string | number>;
    selectedYear: Ref<number>;
}

export const useReportValidation = (options: ValidationOptions) => {
    const { reportType, selectedUser, selectedArea, selectedYear } = options;

    const errors = computed(() => {
        const errs: string[] = [];

        if (!selectedYear.value) {
            errs.push('Selecciona un año');
        }

        if (reportType.value === 'personal' && !selectedUser.value) {
            errs.push('Selecciona una persona');
        }

        if (reportType.value === 'area' && !selectedArea.value) {
            errs.push('Selecciona un área');
        }

        return errs;
    });

    const isValid = computed(() => errors.value.length === 0);

    const getFirstError = computed(() => errors.value[0] || null);

    return {
        errors,
        isValid,
        getFirstError,
        hasErrors: computed(() => errors.value.length > 0),
    };
};
