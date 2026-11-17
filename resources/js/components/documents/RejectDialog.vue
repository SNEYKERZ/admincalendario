<script setup lang="ts">
import axios from 'axios';
import { ref, watch } from 'vue';
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
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useNotification } from '@/composables/useNotification';
import type { DocumentRow } from './types';

const props = defineProps<{ document: DocumentRow | null }>();
const emit = defineEmits<{ close: []; rejected: [] }>();

const notify = useNotification();
const reason = ref('');
const submitting = ref(false);

watch(
    () => props.document,
    (doc) => {
        if (doc) reason.value = '';
    },
);

const submit = async () => {
    if (!props.document) return;
    submitting.value = true;
    try {
        await axios.post(`/documents/${props.document.id}/reject`, {
            reason: reason.value.trim(),
        });
        notify.success('Documento rechazado');
        emit('rejected');
        emit('close');
    } catch (e: any) {
        notify.error(e?.response?.data?.message ?? 'No fue posible rechazar el documento');
    } finally {
        submitting.value = false;
    }
};
</script>

<template>
    <Dialog
        :open="!!document"
        @update:open="(open: boolean) => !open && !submitting && emit('close')"
    >
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Rechazar documento</DialogTitle>
                <DialogDescription>
                    «{{ document?.title }}» quedará marcado como rechazado y no podrá firmarse.
                </DialogDescription>
            </DialogHeader>
            <FormField
                label="Motivo del rechazo"
                for="reject-reason"
                hint="Opcional. Ayuda a quien emitió el documento a corregirlo."
            >
                <Textarea
                    id="reject-reason"
                    v-model="reason"
                    rows="3"
                    placeholder="Ej. Los datos de identificación no coinciden"
                />
            </FormField>
            <DialogFooter>
                <Button variant="outline" :disabled="submitting" @click="emit('close')">
                    Cancelar
                </Button>
                <Button variant="destructive" :disabled="submitting" @click="submit">
                    <Spinner v-if="submitting" />
                    {{ submitting ? 'Rechazando…' : 'Rechazar documento' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
