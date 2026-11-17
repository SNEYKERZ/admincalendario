<script setup lang="ts">
import { computed } from 'vue';
import {
    getStatusDef,
    STATUS_DOT_CLASSES,
    STATUS_TONE_CLASSES
    
} from '@/lib/status';
import type {StatusTone} from '@/lib/status';
import { cn } from '@/lib/utils';

const props = defineProps<{
    status?: string | null;
    /** Sobrescribe la etiqueta mapeada */
    label?: string;
    /** Sobrescribe el tono mapeado */
    tone?: StatusTone;
    dot?: boolean;
    class?: string;
}>();

const def = computed(() => getStatusDef(props.status));
const tone = computed<StatusTone>(() => props.tone ?? def.value.tone);
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex h-5 w-fit shrink-0 items-center gap-1.5 rounded-full border px-2 text-xs font-medium whitespace-nowrap',
                STATUS_TONE_CLASSES[tone],
                props.class,
            )
        "
    >
        <span
            v-if="dot !== false"
            aria-hidden="true"
            :class="cn('size-1.5 rounded-full', STATUS_DOT_CLASSES[tone])"
        />
        <slot>{{ label ?? def.label }}</slot>
    </span>
</template>
