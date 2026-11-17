<script setup lang="ts">
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

/** Etiqueta + control + ayuda + error de validación junto al campo. */
const props = defineProps<{
    label?: string;
    for?: string;
    error?: string | null;
    hint?: string;
    required?: boolean;
    class?: string;
}>();
</script>

<template>
    <div :class="cn('grid content-start gap-1.5', props.class)">
        <Label v-if="label" :for="props.for" class="text-[13px]">
            {{ label }}
            <span v-if="required" class="text-destructive" aria-hidden="true"
                >*</span
            >
        </Label>
        <slot />
        <p v-if="error" class="text-xs text-destructive" role="alert">
            {{ error }}
        </p>
        <p v-else-if="hint" class="text-xs text-muted-foreground">
            {{ hint }}
        </p>
    </div>
</template>
