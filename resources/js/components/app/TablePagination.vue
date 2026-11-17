<script setup lang="ts">
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

defineProps<{
    page: number;
    lastPage: number;
    total?: number;
    label?: string;
}>();

const emit = defineEmits<{ change: [page: number] }>();
</script>

<template>
    <div
        class="flex flex-col gap-2 text-[13px] text-muted-foreground sm:flex-row sm:items-center sm:justify-between"
    >
        <span class="tabular-nums">
            Página {{ page }} de {{ Math.max(lastPage, 1) }}
            <template v-if="total !== undefined">
                · {{ total }} {{ label ?? 'registros' }}
            </template>
        </span>
        <div class="flex gap-2">
            <Button
                variant="outline"
                size="sm"
                :disabled="page <= 1"
                aria-label="Página anterior"
                @click="emit('change', page - 1)"
            >
                <ChevronLeft /> Anterior
            </Button>
            <Button
                variant="outline"
                size="sm"
                :disabled="page >= lastPage"
                aria-label="Página siguiente"
                @click="emit('change', page + 1)"
            >
                Siguiente <ChevronRight />
            </Button>
        </div>
    </div>
</template>
