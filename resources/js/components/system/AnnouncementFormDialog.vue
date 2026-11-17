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
import type { AnnouncementForm } from './types';

defineProps<{
    editing: boolean;
    saving: boolean;
    errors: Record<string, string>;
}>();
const emit = defineEmits<{ save: [] }>();

const open = defineModel<boolean>('open', { required: true });
const form = defineModel<AnnouncementForm>('form', { required: true });
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{
                    editing ? 'Editar anuncio' : 'Nuevo anuncio'
                }}</DialogTitle>
                <DialogDescription>
                    Se mostrará a los administradores de las empresas.
                </DialogDescription>
            </DialogHeader>

            <form
                id="announcement-form"
                class="grid gap-4"
                @submit.prevent="emit('save')"
            >
                <FormField
                    label="Título"
                    for="ann_title"
                    required
                    :error="errors.title"
                >
                    <Input
                        id="ann_title"
                        v-model="form.title"
                        required
                        :aria-invalid="!!errors.title"
                    />
                </FormField>
                <FormField
                    label="Mensaje"
                    for="ann_message"
                    required
                    :error="errors.message"
                >
                    <Textarea
                        id="ann_message"
                        v-model="form.message"
                        rows="3"
                        :aria-invalid="!!errors.message"
                    />
                </FormField>
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField label="Tipo" for="ann_type" :error="errors.type">
                        <select
                            id="ann_type"
                            v-model="form.type"
                            class="input"
                        >
                            <option value="general">General</option>
                            <option value="expiring">Por vencer</option>
                            <option value="expired">Vencido</option>
                        </select>
                    </FormField>
                    <FormField
                        v-if="form.type !== 'general'"
                        label="Días antes del vencimiento"
                        for="ann_days"
                        :error="errors.days_before"
                    >
                        <Input
                            id="ann_days"
                            v-model.number="form.days_before"
                            type="number"
                            min="1"
                            max="365"
                        />
                    </FormField>
                </div>
                <div class="flex items-center gap-2">
                    <Checkbox id="ann_active" v-model="form.is_active" />
                    <Label for="ann_active" class="font-normal"
                        >Anuncio activo</Label
                    >
                </div>
            </form>

            <DialogFooter>
                <Button variant="outline" @click="open = false">
                    Cancelar
                </Button>
                <Button
                    type="submit"
                    form="announcement-form"
                    :disabled="saving"
                >
                    <Spinner v-if="saving" />
                    {{ saving ? 'Guardando…' : 'Guardar anuncio' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
