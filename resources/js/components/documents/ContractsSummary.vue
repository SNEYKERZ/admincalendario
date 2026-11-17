<script setup lang="ts">
import { FileText } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import EmptyState from '@/components/app/EmptyState.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { modeLabel, signatureBadge } from './types';
import type { DocumentRow } from './types';

const props = defineProps<{ contracts: DocumentRow[] }>();

const INITIAL = 6;
const expanded = ref(false);
const visible = computed(() =>
    expanded.value ? props.contracts : props.contracts.slice(0, INITIAL),
);
</script>

<template>
    <section class="min-w-0 overflow-hidden rounded-xl border bg-card">
        <div class="flex items-center justify-between gap-2 border-b px-4 py-3 sm:px-5">
            <div>
                <h2 class="text-[15px] font-semibold tracking-[-0.01em]">Contratos</h2>
                <p class="text-[13px] text-muted-foreground">
                    Plantillas, contratos derivados y contratos por colaborador.
                </p>
            </div>
            <span class="text-xs text-muted-foreground tabular-nums">
                {{ contracts.length }}
            </span>
        </div>

        <EmptyState
            v-if="contracts.length === 0"
            compact
            :icon="FileText"
            title="No hay contratos registrados"
        />
        <template v-else>
            <ul class="grid gap-px bg-border sm:grid-cols-2 xl:grid-cols-3">
                <li
                    v-for="doc in visible"
                    :key="doc.id"
                    class="flex min-w-0 flex-col gap-1.5 bg-card px-4 py-3"
                >
                    <div class="flex items-start justify-between gap-2">
                        <p class="min-w-0 truncate text-sm font-medium" :title="doc.title">
                            {{ doc.title }}
                        </p>
                        <StatusBadge :status="doc.status" />
                    </div>
                    <p class="truncate text-xs text-muted-foreground">
                        {{ modeLabel(doc.document_mode) }} ·
                        {{ doc.user?.name ?? 'Sin colaborador' }}
                        <template v-if="doc.area"> · {{ doc.area.name }}</template>
                    </p>
                    <div class="flex items-center gap-2 text-xs text-muted-foreground">
                        <StatusBadge
                            :status="signatureBadge(doc.signature_status).status"
                            :label="signatureBadge(doc.signature_status).label"
                            :dot="false"
                        />
                        <span v-if="doc.template" class="truncate">
                            Base: {{ doc.template.title }}
                        </span>
                    </div>
                </li>
            </ul>
            <div
                v-if="contracts.length > INITIAL"
                class="flex justify-center border-t px-4 py-2"
            >
                <Button variant="ghost" size="sm" @click="expanded = !expanded">
                    {{ expanded ? 'Ver menos' : `Ver los ${contracts.length} contratos` }}
                </Button>
            </div>
        </template>
    </section>
</template>
