<script setup lang="ts">
import type { Component } from 'vue';
import { computed } from 'vue';
import { Skeleton } from '@/components/ui/skeleton';
import type { StatusTone } from '@/lib/status';
import { cn } from '@/lib/utils';

const props = defineProps<{
    label: string;
    value?: string | number | null;
    hint?: string;
    icon?: Component;
    tone?: StatusTone;
    loading?: boolean;
    class?: string;
}>();

const iconTone = computed(() => {
    switch (props.tone) {
        case 'success':
            return 'bg-success-soft text-success';
        case 'warning':
            return 'bg-warning-soft text-warning';
        case 'destructive':
            return 'bg-destructive-soft text-destructive';
        case 'info':
            return 'bg-info-soft text-info';
        case 'primary':
            return 'bg-primary-soft text-primary';
        default:
            return 'bg-muted text-muted-foreground';
    }
});
</script>

<template>
    <div
        :class="
            cn(
                'flex min-w-0 flex-col gap-2 rounded-xl border bg-card p-4 text-card-foreground',
                props.class,
            )
        "
    >
        <div class="flex items-center justify-between gap-2">
            <span
                class="truncate text-[13px] font-medium text-muted-foreground"
            >
                {{ label }}
            </span>
            <span
                v-if="icon"
                :class="
                    cn(
                        'flex size-7 shrink-0 items-center justify-center rounded-md',
                        iconTone,
                    )
                "
                aria-hidden="true"
            >
                <component :is="icon" class="size-4" />
            </span>
        </div>
        <Skeleton v-if="loading" class="h-7 w-16" />
        <div
            v-else
            class="text-2xl leading-none font-semibold tracking-tight tabular-nums"
        >
            <slot>{{ value ?? '—' }}</slot>
        </div>
        <p v-if="hint || $slots.hint" class="text-xs text-muted-foreground">
            <slot name="hint">{{ hint }}</slot>
        </p>
    </div>
</template>
