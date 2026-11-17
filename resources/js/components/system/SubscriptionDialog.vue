<script setup lang="ts">
import { Check } from 'lucide-vue-next';
import EmptyState from '@/components/app/EmptyState.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { formatCurrency } from './types';
import type { SubscriptionPlan, SystemAdmin } from './types';

defineProps<{
    admin: SystemAdmin | null;
    plans: SubscriptionPlan[];
    saving: boolean;
}>();
const emit = defineEmits<{ save: [] }>();

const open = defineModel<boolean>('open', { required: true });
const planId = defineModel<number | null>('planId', { required: true });
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Activar suscripción</DialogTitle>
                <DialogDescription>
                    Selecciona el plan para
                    <strong class="text-foreground">{{ admin?.name }}</strong>.
                </DialogDescription>
            </DialogHeader>

            <EmptyState
                v-if="plans.length === 0"
                compact
                title="No hay planes disponibles"
                description="Crea un plan en la pestaña Planes."
            />
            <div
                v-else
                role="radiogroup"
                aria-label="Plan de suscripción"
                class="grid gap-2"
            >
                <label
                    v-for="plan in plans"
                    :key="plan.id"
                    :class="
                        cn(
                            'flex cursor-pointer items-center justify-between gap-3 rounded-lg border p-3 transition-colors hover:bg-accent has-[:focus-visible]:ring-[3px] has-[:focus-visible]:ring-ring/40',
                            planId === plan.id &&
                                'border-primary bg-primary-soft hover:bg-primary-soft',
                        )
                    "
                >
                    <span class="flex items-center gap-3">
                        <input
                            v-model="planId"
                            type="radio"
                            name="plan_id"
                            :value="plan.id"
                            class="sr-only"
                        />
                        <span
                            :class="
                                cn(
                                    'flex size-4 items-center justify-center rounded-full border',
                                    planId === plan.id
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'border-input',
                                )
                            "
                            aria-hidden="true"
                        >
                            <Check v-if="planId === plan.id" class="size-3" />
                        </span>
                        <span>
                            <span class="block text-sm font-medium">{{
                                plan.name
                            }}</span>
                            <span class="block text-xs text-muted-foreground">
                                {{ plan.duration_days }} días
                            </span>
                        </span>
                    </span>
                    <span class="text-sm font-semibold tabular-nums">{{
                        formatCurrency(plan.price_cop, 'COP')
                    }}</span>
                </label>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="open = false">
                    Cancelar
                </Button>
                <Button :disabled="saving || !planId" @click="emit('save')">
                    <Spinner v-if="saving" />
                    {{ saving ? 'Activando…' : 'Activar suscripción' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
