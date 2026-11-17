<script setup lang="ts">
import { Layers, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import EmptyState from '@/components/app/EmptyState.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { formatCurrency  } from './types';
import type {SubscriptionPlan} from './types';

defineProps<{ plans: SubscriptionPlan[] }>();
const emit = defineEmits<{
    create: [];
    edit: [plan: SubscriptionPlan];
    delete: [plan: SubscriptionPlan];
}>();
</script>

<template>
    <div class="grid gap-4">
        <div class="flex items-center justify-between gap-2">
            <p class="text-sm text-muted-foreground">
                {{ plans.length }}
                {{ plans.length === 1 ? 'plan' : 'planes' }} de suscripción
            </p>
            <Button @click="emit('create')">
                <Plus /> Nuevo plan
            </Button>
        </div>

        <div v-if="plans.length === 0" class="rounded-xl border bg-card">
            <EmptyState
                :icon="Layers"
                title="No hay planes de suscripción"
                description="Crea el primer plan para poder activar suscripciones a los administradores."
            >
                <Button variant="outline" @click="emit('create')">
                    <Plus /> Crear plan
                </Button>
            </EmptyState>
        </div>

        <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="plan in plans"
                :key="plan.id"
                class="flex flex-col rounded-xl border bg-card p-5"
            >
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <h3 class="truncate font-semibold">{{ plan.name }}</h3>
                        <p class="text-[13px] text-muted-foreground">
                            {{ plan.duration_days }} días
                        </p>
                    </div>
                    <StatusBadge
                        :status="plan.is_active ? 'activo' : 'inactivo'"
                    />
                </div>

                <div class="mt-4 flex flex-wrap items-baseline gap-x-2 gap-y-1">
                    <span
                        class="text-2xl font-semibold tracking-tight tabular-nums"
                        >{{ formatCurrency(plan.price_cop, 'COP') }}</span
                    >
                    <span class="text-sm text-muted-foreground tabular-nums"
                        >/ {{ formatCurrency(plan.price_usd, 'USD') }}</span
                    >
                </div>
                <div
                    v-if="plan.discount_percentage > 0"
                    class="mt-2 flex items-center gap-2 text-[13px]"
                >
                    <span
                        class="text-muted-foreground line-through tabular-nums"
                    >
                        {{
                            formatCurrency(
                                plan.original_price_cop || plan.price_cop * 1.3,
                                'COP',
                            )
                        }}
                    </span>
                    <StatusBadge
                        tone="success"
                        :dot="false"
                        :label="`-${plan.discount_percentage}% de descuento`"
                    />
                </div>
                <p
                    v-if="plan.description"
                    class="mt-3 text-sm text-muted-foreground"
                >
                    {{ plan.description }}
                </p>

                <div class="mt-auto flex gap-2 pt-4">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="emit('edit', plan)"
                    >
                        <Pencil /> Editar
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        class="text-destructive hover:bg-destructive-soft hover:text-destructive"
                        @click="emit('delete', plan)"
                    >
                        <Trash2 /> Eliminar
                    </Button>
                </div>
            </article>
        </div>
    </div>
</template>
