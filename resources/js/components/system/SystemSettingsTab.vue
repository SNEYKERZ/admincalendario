<script setup lang="ts">
import { ArrowRightLeft } from 'lucide-vue-next';
import { computed } from 'vue';
import FormField from '@/components/app/FormField.vue';
import SectionCard from '@/components/app/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatCurrency  } from './types';
import type {SubscriptionSettings} from './types';

defineProps<{ saving: boolean }>();
const emit = defineEmits<{ save: [] }>();

const settings = defineModel<SubscriptionSettings>('settings', {
    required: true,
});

const copPerUsd = computed(() => {
    const cop = Number(settings.value.cop_reference_amount);
    const usd = Number(settings.value.usd_reference_amount);
    if (!cop || !usd || cop <= 0 || usd <= 0) return null;
    return cop / usd;
});
</script>

<template>
    <form class="grid gap-4" @submit.prevent="emit('save')">
        <SectionCard
            title="Conversión de moneda"
            description="Define la equivalencia entre pesos colombianos y dólares. Al guardar se recalculan los precios en USD de los planes."
        >
            <div class="grid gap-4 md:grid-cols-2">
                <FormField
                    label="Pesos colombianos (COP)"
                    for="cop_reference_amount"
                    hint="Ej.: 20.000 COP"
                >
                    <Input
                        id="cop_reference_amount"
                        v-model.number="settings.cop_reference_amount"
                        type="number"
                        min="0"
                        placeholder="20000"
                    />
                </FormField>
                <FormField
                    label="Dólares (USD)"
                    for="usd_reference_amount"
                    hint="Ej.: 5 USD"
                >
                    <Input
                        id="usd_reference_amount"
                        v-model.number="settings.usd_reference_amount"
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="5"
                    />
                </FormField>
            </div>
            <div
                class="mt-4 flex items-center gap-2 rounded-lg border border-info/20 bg-info-soft px-3 py-2.5 text-sm text-info"
                role="status"
            >
                <ArrowRightLeft class="size-4 shrink-0" aria-hidden="true" />
                <span v-if="copPerUsd">
                    <strong class="font-semibold">Tasa resultante:</strong>
                    1 USD = {{ formatCurrency(copPerUsd, 'COP') }}
                </span>
                <span v-else
                    >Ingresa ambos montos para calcular la tasa de
                    conversión.</span
                >
            </div>
        </SectionCard>

        <div class="grid gap-4 md:grid-cols-2">
            <SectionCard
                title="Anuncios de vencimiento"
                description="Cuántos días antes del vencimiento se muestran los avisos a los administradores."
            >
                <FormField
                    label="Días antes del vencimiento"
                    for="show_ads_days_before"
                    class="max-w-xs"
                >
                    <Input
                        id="show_ads_days_before"
                        v-model.number="settings.show_ads_days_before"
                        type="number"
                        min="1"
                        max="30"
                    />
                </FormField>
            </SectionCard>

            <SectionCard
                title="Pagos"
                description="Permite que los administradores paguen su suscripción en línea."
            >
                <div class="flex items-center gap-2">
                    <Checkbox
                        id="enable_payments"
                        v-model="settings.enable_payments"
                    />
                    <Label for="enable_payments" class="font-normal">
                        Habilitar pagos en línea
                    </Label>
                </div>
            </SectionCard>
        </div>

        <div class="flex justify-end">
            <Button type="submit" :disabled="saving">
                <Spinner v-if="saving" />
                {{ saving ? 'Guardando…' : 'Guardar configuración' }}
            </Button>
        </div>
    </form>
</template>
