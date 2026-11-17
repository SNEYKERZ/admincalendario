<script setup lang="ts">
import { cn } from '@/lib/utils';

/**
 * Contenedor de sección del panel: cabecera compacta (título, descripción,
 * acciones) + cuerpo. `flush` quita el padding del cuerpo (para tablas).
 */
const props = defineProps<{
    title?: string;
    description?: string;
    flush?: boolean;
    class?: string;
    bodyClass?: string;
}>();
</script>

<template>
    <section
        :class="
            cn(
                'min-w-0 overflow-hidden rounded-xl border bg-card text-card-foreground',
                props.class,
            )
        "
    >
        <div
            v-if="title || $slots.header || $slots.actions"
            class="flex flex-col gap-3 border-b px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5"
        >
            <slot name="header">
                <div class="min-w-0">
                    <h2 class="text-[15px] font-semibold tracking-[-0.01em]">
                        {{ title }}
                    </h2>
                    <p
                        v-if="description"
                        class="mt-0.5 text-[13px] text-muted-foreground"
                    >
                        {{ description }}
                    </p>
                </div>
            </slot>
            <div
                v-if="$slots.actions"
                class="flex flex-wrap items-center gap-2"
            >
                <slot name="actions" />
            </div>
        </div>
        <div :class="cn(flush ? '' : 'p-4 sm:p-5', bodyClass)">
            <slot />
        </div>
        <div
            v-if="$slots.footer"
            class="flex flex-col gap-2 border-t px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5"
        >
            <slot name="footer" />
        </div>
    </section>
</template>
