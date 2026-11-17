<script setup lang="ts">
import FormField from '@/components/app/FormField.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import type { PlanForm } from './types';

defineProps<{
    editing: boolean;
    saving: boolean;
    errors: Record<string, string>;
}>();
const emit = defineEmits<{ save: [] }>();

const open = defineModel<boolean>('open', { required: true });
const form = defineModel<PlanForm>('form', { required: true });
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{
                    editing ? 'Editar plan' : 'Nuevo plan'
                }}</DialogTitle>
                <DialogDescription>
                    El precio en USD se calcula con la tasa de conversión
                    configurada.
                </DialogDescription>
            </DialogHeader>

            <form
                id="plan-form"
                class="grid gap-4"
                @submit.prevent="emit('save')"
            >
                <FormField
                    label="Nombre"
                    for="plan_name"
                    required
                    :error="errors.name"
                >
                    <Input
                        id="plan_name"
                        v-model="form.name"
                        required
                        :aria-invalid="!!errors.name"
                    />
                </FormField>
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField
                        label="Duración (días)"
                        for="plan_duration"
                        required
                        :error="errors.duration_days"
                    >
                        <Input
                            id="plan_duration"
                            v-model.number="form.duration_days"
                            type="number"
                            min="1"
                            :aria-invalid="!!errors.duration_days"
                        />
                    </FormField>
                    <FormField
                        label="Precio (COP)"
                        for="plan_price"
                        required
                        :error="errors.price_cop"
                    >
                        <Input
                            id="plan_price"
                            v-model.number="form.price_cop"
                            type="number"
                            min="0"
                            :aria-invalid="!!errors.price_cop"
                        />
                    </FormField>
                </div>
                <FormField
                    label="Descuento (%)"
                    for="plan_discount"
                    :error="errors.discount_percentage"
                    hint="Déjalo en 0 si el plan no tiene descuento."
                >
                    <Input
                        id="plan_discount"
                        v-model.number="form.discount_percentage"
                        type="number"
                        min="0"
                        max="100"
                        placeholder="0"
                    />
                </FormField>
                <div
                    v-if="form.discount_percentage > 0"
                    class="grid gap-4 sm:grid-cols-2"
                >
                    <FormField
                        label="Precio original (COP)"
                        for="plan_orig_cop"
                        :error="errors.original_price_cop"
                    >
                        <Input
                            id="plan_orig_cop"
                            v-model.number="form.original_price_cop"
                            type="number"
                            min="0"
                        />
                    </FormField>
                    <FormField
                        label="Precio original (USD)"
                        for="plan_orig_usd"
                        :error="errors.original_price_usd"
                    >
                        <Input
                            id="plan_orig_usd"
                            v-model.number="form.original_price_usd"
                            type="number"
                            min="0"
                            step="0.01"
                        />
                    </FormField>
                </div>
                <FormField
                    label="Descripción"
                    for="plan_description"
                    :error="errors.description"
                >
                    <Textarea
                        id="plan_description"
                        v-model="form.description"
                        rows="2"
                    />
                </FormField>
                <div class="flex items-center gap-2">
                    <Checkbox id="plan_active" v-model="form.is_active" />
                    <Label for="plan_active" class="font-normal"
                        >Plan activo (visible para contratar)</Label
                    >
                </div>
            </form>

            <DialogFooter>
                <Button variant="outline" @click="open = false">
                    Cancelar
                </Button>
                <Button type="submit" form="plan-form" :disabled="saving">
                    <Spinner v-if="saving" />
                    {{ saving ? 'Guardando…' : 'Guardar plan' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
