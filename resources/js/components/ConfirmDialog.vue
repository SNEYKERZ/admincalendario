<script setup lang="ts">
import { ref } from 'vue';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

type Props = {
    title?: string;
    description?: string;
    confirmText?: string;
    cancelText?: string;
    variant?: 'default' | 'destructive';
    trigger?: any;
};

const props = withDefaults(defineProps<Props>(), {
    title: 'Confirmar acción',
    description: '¿Está seguro de que desea continuar?',
    confirmText: 'Confirmar',
    cancelText: 'Cancelar',
    variant: 'default',
});

const emit = defineEmits<{
    confirm: [];
}>();

const open = ref(false);

const handleConfirm = () => {
    emit('confirm');
    open.value = false;
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot name="trigger">
                <Button
                    :variant="
                        variant === 'destructive' ? 'destructive' : 'default'
                    "
                >
                    {{ confirmText }}
                </Button>
            </slot>
        </DialogTrigger>
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">
                        {{ cancelText }}
                    </Button>
                </DialogClose>
                <Button
                    :variant="
                        variant === 'destructive' ? 'destructive' : 'default'
                    "
                    @click="handleConfirm"
                >
                    {{ confirmText }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
