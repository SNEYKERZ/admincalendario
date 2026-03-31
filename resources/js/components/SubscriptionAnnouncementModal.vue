<script setup lang="ts">
import { ref, computed } from 'vue';

interface Props {
    show: boolean;
    type: 'expiring' | 'payment_required';
    daysRemaining?: number;
    planName?: string;
    expiresAt?: string;
}

interface Emits {
    (e: 'close'): void;
    (e: 'renew'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const title = computed(() => {
    if (props.type === 'expiring') {
        return 'Tu suscripción está por vencer';
    }
    return 'Pago requerido';
});

const message = computed(() => {
    if (props.type === 'expiring') {
        return `Tu suscripción ${props.planName} vence en ${props.daysRemaining} días. Para seguir usando todas las funciones, renueva pronto.`;
    }
    return 'Tu suscripción ha vencido o no tienes una suscripción activa. Por favor, contacta al administrador del sistema para continuar usando las funciones completas.';
});

const iconColor = computed(() => {
    if (props.type === 'expiring') return 'text-amber-600';
    return 'text-red-600';
});
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @click.self="emit('close')"
    >
        <div class="w-full max-w-md rounded-xl bg-white p-6 dark:bg-gray-800">
            <div class="flex items-center gap-4">
                <div
                    :class="[
                        'flex h-12 w-12 items-center justify-center rounded-full',
                        type === 'expiring'
                            ? 'bg-amber-100 dark:bg-amber-900/30'
                            : 'bg-red-100 dark:bg-red-900/30',
                    ]"
                >
                    <svg
                        v-if="type === 'expiring'"
                        class="h-6 w-6 text-amber-600 dark:text-amber-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    </svg>
                    <svg
                        v-else
                        class="h-6 w-6 text-red-600 dark:text-red-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"
                        />
                    </svg>
                </div>
                <div>
                    <h3
                        class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                    >
                        {{ title }}
                    </h3>
                    <p class="text-sm text-gray-500">
                        {{
                            expiresAt
                                ? `Vence: ${new Date(expiresAt).toLocaleDateString('es-CO')}`
                                : 'Sin suscripción activa'
                        }}
                    </p>
                </div>
            </div>

            <p class="mt-4 text-gray-600 dark:text-gray-300">
                {{ message }}
            </p>

            <div class="mt-6 flex justify-end gap-2">
                <button
                    v-if="type === 'expiring'"
                    @click="emit('close')"
                    class="btn-secondary"
                >
                    Más tarde
                </button>
                <button
                    v-if="type === 'expiring'"
                    @click="emit('renew')"
                    class="btn-primary"
                >
                    Renovar ahora
                </button>
                <button v-else @click="emit('close')" class="btn-secondary">
                    Entendido
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.btn-primary {
    display: inline-flex;
    align-items: center;
    border-radius: 0.375rem;
    background-color: #2563eb;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: white;
}
.btn-primary:hover {
    background-color: #1d4ed8;
}
.btn-secondary {
    display: inline-flex;
    align-items: center;
    border-radius: 0.375rem;
    border: 1px solid #d1d5db;
    background-color: white;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
}
.dark .btn-secondary {
    background-color: #1f2937;
    border-color: #374151;
    color: #d1d5db;
}
</style>
