<script setup lang="ts">
import { AlertTriangle } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { confirmState, settleConfirm } from '@/composables/useConfirm';

const onOpenChange = (open: boolean) => {
    if (!open) settleConfirm(false);
};
</script>

<template>
    <Dialog :open="confirmState.open" @update:open="onOpenChange">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <div class="flex items-start gap-3">
                    <div
                        v-if="confirmState.variant === 'destructive'"
                        class="flex size-9 shrink-0 items-center justify-center rounded-full bg-destructive-soft text-destructive"
                        aria-hidden="true"
                    >
                        <AlertTriangle class="size-4" />
                    </div>
                    <div class="grid gap-1.5 text-left">
                        <DialogTitle>{{ confirmState.title }}</DialogTitle>
                        <DialogDescription>
                            {{ confirmState.description }}
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <Button variant="outline" @click="settleConfirm(false)">
                    {{ confirmState.cancelText }}
                </Button>
                <Button
                    :variant="
                        confirmState.variant === 'destructive'
                            ? 'destructive'
                            : 'default'
                    "
                    @click="settleConfirm(true)"
                >
                    {{ confirmState.confirmText }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
