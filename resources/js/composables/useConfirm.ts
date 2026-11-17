import { reactive } from 'vue';

export type ConfirmOptions = {
    title?: string;
    description?: string;
    confirmText?: string;
    cancelText?: string;
    variant?: 'default' | 'destructive';
};

type ConfirmState = Required<ConfirmOptions> & {
    open: boolean;
    resolve: ((value: boolean) => void) | null;
};

/** Estado global único: <ConfirmHost /> (montado en AppLayout) lo renderiza. */
export const confirmState = reactive<ConfirmState>({
    open: false,
    title: 'Confirmar acción',
    description: '¿Seguro que deseas continuar?',
    confirmText: 'Confirmar',
    cancelText: 'Cancelar',
    variant: 'default',
    resolve: null,
});

/**
 * Sustituto accesible de window.confirm():
 *   const confirm = useConfirm();
 *   if (!(await confirm({ title: 'Eliminar área', variant: 'destructive' }))) return;
 */
export function useConfirm() {
    return (options: ConfirmOptions | string = {}): Promise<boolean> => {
        const opts: ConfirmOptions =
            typeof options === 'string' ? { description: options } : options;

        // Si había un diálogo abierto, se resuelve como cancelado.
        confirmState.resolve?.(false);

        confirmState.title = opts.title ?? 'Confirmar acción';
        confirmState.description =
            opts.description ?? '¿Seguro que deseas continuar?';
        confirmState.confirmText =
            opts.confirmText ??
            (opts.variant === 'destructive' ? 'Eliminar' : 'Confirmar');
        confirmState.cancelText = opts.cancelText ?? 'Cancelar';
        confirmState.variant = opts.variant ?? 'default';
        confirmState.open = true;

        return new Promise<boolean>((resolve) => {
            confirmState.resolve = resolve;
        });
    };
}

export function settleConfirm(value: boolean) {
    const resolve = confirmState.resolve;
    confirmState.resolve = null;
    confirmState.open = false;
    resolve?.(value);
}
