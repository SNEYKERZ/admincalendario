<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    title: string;
    description?: string;
    icon?: Component;
}>();
</script>

<template>
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
    >
        <div class="flex min-w-0 items-start gap-3">
            <div
                v-if="icon"
                class="mt-0.5 hidden size-9 shrink-0 items-center justify-center rounded-lg border bg-card text-muted-foreground sm:flex"
                aria-hidden="true"
            >
                <component :is="icon" class="size-[18px]" />
            </div>
            <div class="min-w-0">
                <h1
                    class="text-xl font-semibold tracking-[-0.015em] text-balance text-foreground sm:text-2xl"
                >
                    {{ title }}
                </h1>
                <p
                    v-if="description || $slots.description"
                    class="mt-1 max-w-[70ch] text-sm text-muted-foreground"
                >
                    <slot name="description">{{ description }}</slot>
                </p>
            </div>
        </div>
        <div
            v-if="$slots.actions"
            class="flex flex-wrap items-center gap-2 sm:shrink-0 sm:justify-end"
        >
            <slot name="actions" />
        </div>
    </header>
</template>
