<script setup lang="ts">
import { cn } from '@/lib/utils';

const props = defineProps<{ class?: string; narrow?: boolean }>();
</script>

<template>
    <div
        :class="
            cn(
                'mx-auto flex w-full min-w-0 flex-col gap-6 px-4 py-5 sm:px-6 sm:py-6',
                narrow ? 'max-w-4xl' : 'max-w-[1400px]',
                props.class,
            )
        "
    >
        <slot />
    </div>
</template>
