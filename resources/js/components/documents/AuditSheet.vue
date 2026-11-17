<script setup lang="ts">
import { History } from 'lucide-vue-next';
import EmptyState from '@/components/app/EmptyState.vue';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Skeleton } from '@/components/ui/skeleton';
import { auditActionLabel, formatDocDate } from './types';
import type { DocumentAudit, DocumentRow } from './types';

defineProps<{
    document: DocumentRow | null;
    audits: DocumentAudit[];
    loading: boolean;
}>();

const emit = defineEmits<{ close: [] }>();
</script>

<template>
    <Sheet :open="!!document" @update:open="(open: boolean) => !open && emit('close')">
        <SheetContent class="w-full gap-0 bg-card p-0 sm:max-w-md">
            <SheetHeader class="border-b px-5 py-4">
                <SheetTitle>Historial del documento</SheetTitle>
                <SheetDescription>{{ document?.title }}</SheetDescription>
            </SheetHeader>
            <div class="flex-1 overflow-y-auto px-5 py-4">
                <div v-if="loading" class="space-y-3" aria-busy="true">
                    <Skeleton v-for="i in 4" :key="i" class="h-12 w-full" />
                </div>
                <EmptyState
                    v-else-if="audits.length === 0"
                    compact
                    :icon="History"
                    title="Sin eventos registrados"
                    description="Aún no hay acciones sobre este documento."
                />
                <ol v-else class="relative space-y-4 border-l pl-5">
                    <li v-for="audit in audits" :key="audit.id" class="relative">
                        <span
                            class="absolute top-1.5 -left-[25px] size-2.5 rounded-full border-2 border-card bg-primary"
                            aria-hidden="true"
                        />
                        <p class="text-sm font-medium">
                            {{ auditActionLabel(audit.action) }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ audit.user?.name ?? 'Sistema' }} ·
                            <time :datetime="audit.created_at">{{
                                formatDocDate(audit.created_at, true)
                            }}</time>
                        </p>
                    </li>
                </ol>
            </div>
        </SheetContent>
    </Sheet>
</template>
