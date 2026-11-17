<script setup lang="ts">
import { Moon, Sun } from 'lucide-vue-next';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useAppearance } from '@/composables/useAppearance';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { resolvedAppearance, updateAppearance } = useAppearance();

const toggleTheme = () => {
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');
};
</script>

<template>
    <header
        class="sticky top-0 z-20 flex h-12 shrink-0 items-center justify-between gap-2 border-b bg-background/85 px-3 backdrop-blur supports-[backdrop-filter]:bg-background/70 md:rounded-t-xl md:px-4"
    >
        <div class="flex min-w-0 items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <div class="h-4 w-px bg-border" aria-hidden="true" />
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <Button
            variant="ghost"
            size="icon-sm"
            :aria-label="
                resolvedAppearance === 'dark'
                    ? 'Cambiar a modo claro'
                    : 'Cambiar a modo oscuro'
            "
            :title="
                resolvedAppearance === 'dark'
                    ? 'Cambiar a modo claro'
                    : 'Cambiar a modo oscuro'
            "
            @click="toggleTheme"
        >
            <Sun v-if="resolvedAppearance === 'dark'" />
            <Moon v-else />
        </Button>
    </header>
</template>
