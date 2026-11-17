<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import FormField from '@/components/app/FormField.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import {
    calcHours,
    formatHours,
    toDateInput
    
    
} from './utils';
import type {OvertimeFormValues, OvertimeRecord} from './utils';

const props = defineProps<{
    record: OvertimeRecord | null;
    processing?: boolean;
    errors?: Record<string, string>;
    description?: string;
}>();

const emit = defineEmits<{
    close: [];
    submit: [values: OvertimeFormValues];
}>();

const form = ref<OvertimeFormValues>({
    date: '',
    start_time: '',
    end_time: '',
    project: '',
    client: '',
    reason: '',
});
const localError = ref('');

watch(
    () => props.record,
    (r) => {
        localError.value = '';
        if (!r) return;
        form.value = {
            date: toDateInput(r.date),
            start_time: (r.start_time || '').substring(0, 5),
            end_time: (r.end_time || '').substring(0, 5),
            project: r.project || '',
            client: r.client || '',
            reason: r.reason || '',
        };
    },
    { immediate: true },
);

const hours = computed(() =>
    calcHours(form.value.start_time, form.value.end_time),
);

const open = computed({
    get: () => props.record !== null,
    set: (v: boolean) => {
        if (!v) emit('close');
    },
});

const err = (key: string) => props.errors?.[key];

const onSubmit = () => {
    localError.value = '';
    if (hours.value <= 0) {
        localError.value = 'La hora de fin debe ser posterior a la de inicio.';
        return;
    }
    if (form.value.reason.trim().length < 10) {
        localError.value = 'El motivo debe tener al menos 10 caracteres.';
        return;
    }
    emit('submit', { ...form.value });
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Editar registro de horas extra</DialogTitle>
                <DialogDescription>
                    {{
                        description ??
                        'Solo se pueden editar registros pendientes de aprobación.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <form
                id="overtime-edit-form"
                class="grid gap-4"
                @submit.prevent="onSubmit"
            >
                <div class="grid gap-4 sm:grid-cols-3">
                    <FormField
                        label="Fecha"
                        for="ot-edit-date"
                        required
                        :error="err('date')"
                    >
                        <Input
                            id="ot-edit-date"
                            v-model="form.date"
                            type="date"
                            required
                        />
                    </FormField>
                    <FormField
                        label="Inicio"
                        for="ot-edit-start"
                        required
                        :error="err('start_time')"
                    >
                        <Input
                            id="ot-edit-start"
                            v-model="form.start_time"
                            type="time"
                            required
                        />
                    </FormField>
                    <FormField
                        label="Fin"
                        for="ot-edit-end"
                        required
                        :error="err('end_time')"
                    >
                        <Input
                            id="ot-edit-end"
                            v-model="form.end_time"
                            type="time"
                            required
                        />
                    </FormField>
                </div>
                <p class="-mt-2 text-xs text-muted-foreground">
                    Total calculado:
                    <span class="font-medium text-foreground tabular-nums">{{
                        formatHours(Math.max(hours, 0))
                    }}</span>
                </p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField
                        label="Proyecto"
                        for="ot-edit-project"
                        :error="err('project')"
                    >
                        <Input
                            id="ot-edit-project"
                            v-model="form.project"
                            placeholder="Código o nombre"
                        />
                    </FormField>
                    <FormField
                        label="Cliente"
                        for="ot-edit-client"
                        :error="err('client')"
                    >
                        <Input
                            id="ot-edit-client"
                            v-model="form.client"
                            placeholder="Nombre del cliente"
                        />
                    </FormField>
                </div>
                <FormField
                    label="Motivo"
                    for="ot-edit-reason"
                    required
                    hint="Mínimo 10 caracteres."
                    :error="err('reason')"
                >
                    <Textarea
                        id="ot-edit-reason"
                        v-model="form.reason"
                        rows="3"
                        required
                        minlength="10"
                    />
                </FormField>
                <p
                    v-if="localError || err('general')"
                    class="rounded-md border border-destructive/20 bg-destructive-soft px-3 py-2 text-sm text-destructive"
                    role="alert"
                >
                    {{ localError || err('general') }}
                </p>
            </form>

            <DialogFooter>
                <Button
                    variant="outline"
                    type="button"
                    :disabled="processing"
                    @click="emit('close')"
                >
                    Cancelar
                </Button>
                <Button
                    type="submit"
                    form="overtime-edit-form"
                    :disabled="processing"
                >
                    <Spinner v-if="processing" />
                    {{ processing ? 'Guardando…' : 'Guardar cambios' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
