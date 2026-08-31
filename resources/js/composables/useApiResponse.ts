import { useNotification } from './useNotification';

interface ValidationSchema {
    [key: string]: (value: any) => boolean;
}

export const useApiResponse = () => {
    const { error } = useNotification();

    const validateResponse = <T>(
        data: any,
        schema: ValidationSchema,
        errorMessage: string = 'Estructura de datos inválida'
    ): data is T => {
        for (const [key, validator] of Object.entries(schema)) {
            if (!validator(data[key])) {
                error(`${errorMessage}: campo "${key}" inválido`);
                return false;
            }
        }
        return true;
    };

    const validateArray = (data: any, itemSchema?: ValidationSchema): data is any[] => {
        if (!Array.isArray(data)) {
            error('Se esperaba un array en la respuesta');
            return false;
        }

        if (itemSchema) {
            return data.every((item) => {
                const isValid = Object.entries(itemSchema).every(([key, validator]) =>
                    validator(item[key])
                );
                return isValid;
            });
        }

        return true;
    };

    return {
        validateResponse,
        validateArray,
    };
};
