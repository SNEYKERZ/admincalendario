<script setup lang="ts">
import { computed } from 'vue';
import { SidebarInset } from '@/components/ui/sidebar';
import ImpersonationBadge from '@/components/ImpersonationBadge.vue';

type Props = {
    variant?: 'header' | 'sidebar';
    class?: string;
};

const props = defineProps<Props>();
const className = computed(() => props.class);
</script>

<template>
    <SidebarInset v-if="props.variant === 'sidebar'" :class="className">
        <div class="p-4">
            <ImpersonationBadge />
        </div>
        <slot />
    </SidebarInset>
    <main
        v-else
        class="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-4 rounded-xl"
        :class="className"
    >
        <div class="p-4">
            <ImpersonationBadge />
        </div>
        <slot />
    </main>
</template>
