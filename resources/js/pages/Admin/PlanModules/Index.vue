<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Check, Layers } from 'lucide-vue-next';
import { ref } from 'vue';
import EmptyState from '@/components/app/EmptyState.vue';
import PageContainer from '@/components/app/PageContainer.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import SectionCard from '@/components/app/SectionCard.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import { formatCurrency } from '@/components/system/types';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useNotification } from '@/composables/useNotification';
import AppLayout from '@/layouts/AppLayout.vue';
import { cn } from '@/lib/utils';

interface PlanModule {
    id: number;
    name: string;
    slug: string;
}

interface Plan {
    id: number;
    name: string;
    description: string | null;
    price_cop: number;
    price_usd: number;
    discount_percentage: number | null;
    duration_days: number;
    module_ids: number[];
    modules: PlanModule[];
}

const page = usePage();
const plans = ref<Plan[]>(
    ((page.props as any).plans ?? []).map((p: Plan) => ({
        ...p,
        module_ids: [...(p.module_ids ?? [])],
    })),
);
const allModules = ref<PlanModule[]>((page.props as any).allModules ?? []);
const savingId = ref<number | null>(null);
const notify = useNotification();

const toggleModule = (plan: Plan, moduleId: number) => {
    plan.module_ids = plan.module_ids.includes(moduleId)
        ? plan.module_ids.filter((id) => id !== moduleId)
        : [...plan.module_ids, moduleId];
};

const updatePlan = (plan: Plan) => {
    savingId.value = plan.id;
    router.put(
        `/admin/gestion-sistema/planes/${plan.id}`,
        {
            name: plan.name,
            price_cop: plan.price_cop,
            price_usd: plan.price_usd,
            discount_percentage: plan.discount_percentage,
            duration_days: plan.duration_days,
            description: plan.description,
            module_ids: plan.module_ids,
        },
        {
            preserveScroll: true,
            onSuccess: () =>
                notify.success(`Módulos del plan "${plan.name}" guardados`),
            onError: (errors) => {
                const first = Object.values(errors)[0];
                notify.error(
                    first
                        ? `No se pudo guardar: ${first}`
                        : 'No se pudo guardar el plan.',
                );
            },
            onFinish: () => {
                savingId.value = null;
            },
        },
    );
};
</script>

<template>
    <Head title="Planes y módulos" />
    <AppLayout>
        <PageContainer>
            <PageHeader
                title="Planes y módulos"
                description="Define qué módulos de la aplicación incluye cada plan de suscripción."
                :icon="Layers"
            />

            <div v-if="plans.length === 0" class="rounded-xl border bg-card">
                <EmptyState
                    :icon="Layers"
                    title="No hay planes"
                    description="Crea planes desde Configuración global → Planes."
                />
            </div>

            <SectionCard v-for="plan in plans" :key="plan.id">
                <template #header>
                    <div class="min-w-0">
                        <h2 class="text-[15px] font-semibold">
                            {{ plan.name }}
                        </h2>
                        <p
                            v-if="plan.description"
                            class="mt-0.5 text-[13px] text-muted-foreground"
                        >
                            {{ plan.description }}
                        </p>
                    </div>
                </template>
                <template #actions>
                    <div class="flex items-center gap-3 text-sm">
                        <span class="font-semibold tabular-nums">{{
                            formatCurrency(plan.price_cop || 0, 'COP')
                        }}</span>
                        <span class="text-muted-foreground tabular-nums">
                            {{ formatCurrency(plan.price_usd || 0, 'USD') }} ·
                            {{ plan.duration_days }} días
                        </span>
                        <StatusBadge
                            v-if="plan.discount_percentage"
                            tone="success"
                            :dot="false"
                            :label="`-${plan.discount_percentage}%`"
                        />
                    </div>
                </template>

                <form @submit.prevent="updatePlan(plan)">
                    <fieldset>
                        <legend class="mb-3 text-sm font-medium">
                            Módulos incluidos
                            <span
                                class="font-normal text-muted-foreground tabular-nums"
                            >
                                ({{ plan.module_ids.length }} de
                                {{ allModules.length }})
                            </span>
                        </legend>
                        <div
                            class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3"
                        >
                            <label
                                v-for="module in allModules"
                                :key="module.id"
                                :class="
                                    cn(
                                        'flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition-colors hover:bg-accent has-[:focus-visible]:ring-[3px] has-[:focus-visible]:ring-ring/40',
                                        plan.module_ids.includes(module.id) &&
                                            'border-primary/40 bg-primary-soft hover:bg-primary-soft',
                                    )
                                "
                            >
                                <input
                                    type="checkbox"
                                    class="sr-only"
                                    :checked="plan.module_ids.includes(module.id)"
                                    @change="toggleModule(plan, module.id)"
                                />
                                <span
                                    :class="
                                        cn(
                                            'flex size-4 shrink-0 items-center justify-center rounded-[4px] border',
                                            plan.module_ids.includes(module.id)
                                                ? 'border-primary bg-primary text-primary-foreground'
                                                : 'border-input bg-card',
                                        )
                                    "
                                    aria-hidden="true"
                                >
                                    <Check
                                        v-if="plan.module_ids.includes(module.id)"
                                        class="size-3"
                                    />
                                </span>
                                <span class="min-w-0">
                                    <span
                                        class="block truncate text-sm font-medium"
                                        >{{ module.name }}</span
                                    >
                                    <span
                                        class="block truncate font-mono text-xs text-muted-foreground"
                                        >{{ module.slug }}</span
                                    >
                                </span>
                            </label>
                        </div>
                    </fieldset>

                    <div class="mt-4 flex justify-end">
                        <Button type="submit" :disabled="savingId === plan.id">
                            <Spinner v-if="savingId === plan.id" />
                            {{
                                savingId === plan.id
                                    ? 'Guardando…'
                                    : 'Guardar cambios'
                            }}
                        </Button>
                    </div>
                </form>
            </SectionCard>
        </PageContainer>
    </AppLayout>
</template>
