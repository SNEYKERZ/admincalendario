<script setup lang="ts">
import { Inbox } from 'lucide-vue-next';
import type { Component } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    title: string;
    description?: string;
    icon?: Component;
    compact?: boolean;
    class?: string;
}>();
</script>

<template>
    <div
        :class="
            cn(
                'flex flex-col items-center justify-center text-center',
                compact ? 'gap-2 px-4 py-8' : 'gap-3 px-6 py-14',
                props.class,
            )
        "
    >
        <div
            class="flex size-10 items-center justify-center rounded-full border bg-muted/60 text-muted-foreground"
            aria-hidden="true"
        >
            <component :is="icon ?? Inbox" class="size-5" />
        </div>
        <div class="space-y-1">
            <p class="text-sm font-medium text-foreground">{{ title }}</p>
            <p
                v-if="description"
                class="mx-auto max-w-sm text-sm text-muted-foreground"
            >
                {{ description }}
            </p>
        </div>
        <div
            v-if="$slots.default"
            class="mt-1 flex flex-wrap justify-center gap-2"
        >
            <slot />
        </div>
    </div>
</template>
