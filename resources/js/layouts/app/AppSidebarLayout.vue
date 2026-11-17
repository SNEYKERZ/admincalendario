<script setup lang="ts">
import ConfirmHost from '@/components/app/ConfirmHost.vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import ImpersonationBadge from '@/components/ImpersonationBadge.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="min-w-0 overflow-x-clip">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <ImpersonationBadge />
            <div id="contenido" class="flex min-w-0 flex-1 flex-col">
                <slot />
            </div>
        </AppContent>
    </AppShell>
    <ConfirmHost />
</template>
