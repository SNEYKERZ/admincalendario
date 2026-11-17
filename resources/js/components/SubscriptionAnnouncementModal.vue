<script setup lang="ts">
import { AlertTriangle, CreditCard } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

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

const isExpiring = computed(() => props.type === 'expiring');

const title = computed(() =>
    isExpiring.value ? 'Tu suscripción está por vencer' : 'Suscripción inactiva',
);

const subtitle = computed(() =>
    props.expiresAt
        ? `Vence el ${new Date(props.expiresAt).toLocaleDateString('es-CO')}`
        : 'Sin suscripción activa',
);

const message = computed(() => {
    if (isExpiring.value) {
        const days = props.daysRemaining ?? 0;
        return `Tu plan ${props.planName ?? ''} vence en ${days} ${days === 1 ? 'día' : 'días'}. Renuévalo para seguir usando todas las funciones sin interrupciones.`;
    }
    return 'Tu suscripción venció o no tienes una suscripción activa. Contacta al administrador del sistema para recuperar el acceso completo.';
});

const onOpenChange = (open: boolean) => {
    if (!open) emit('close');
};
</script>

<template>
    <Dialog :open="show" @update:open="onOpenChange">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <div class="flex items-start gap-3">
                    <div
                        :class="[
                            'flex size-10 shrink-0 items-center justify-center rounded-full',
                            isExpiring
                                ? 'bg-warning-soft text-warning'
                                : 'bg-destructive-soft text-destructive',
                        ]"
                        aria-hidden="true"
                    >
                        <AlertTriangle v-if="isExpiring" class="size-5" />
                        <CreditCard v-else class="size-5" />
                    </div>
                    <div class="grid gap-1 text-left">
                        <DialogTitle>{{ title }}</DialogTitle>
                        <p class="text-xs text-muted-foreground">
                            {{ subtitle }}
                        </p>
                    </div>
                </div>
            </DialogHeader>

            <DialogDescription class="text-sm leading-relaxed">
                {{ message }}
            </DialogDescription>

            <DialogFooter>
                <template v-if="isExpiring">
                    <Button variant="outline" @click="emit('close')">
                        Más tarde
                    </Button>
                    <Button @click="emit('renew')">Renovar ahora</Button>
                </template>
                <Button v-else variant="outline" @click="emit('close')">
                    Entendido
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
