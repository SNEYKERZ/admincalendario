<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Building2, LayoutDashboard, LayoutGrid, Users, FileBarChart, FileText, Settings, Cog } from 'lucide-vue-next';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';

import type { NavItem } from '@/types';
import { computed } from 'vue';

const page = usePage();
const user = page.props.auth?.user;
const isAdmin = user?.role === 'admin' || user?.role === 'superadmin';
const isSuperAdmin = user?.role === 'superadmin';

const company = computed(() => (page.props as any).company ?? {});
const companyName = computed(() => company.value?.name || 'Empresa');
const companyLogoUrl = computed(() => company.value?.logo_url || '/logo.png');

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutDashboard,
    },
    {
        title: 'Calendario',
        href: '/calendario',
        icon: LayoutGrid,
    },
    {
        title: 'Comunidad',
        href: '/comunidad',
        icon: Users,
    },
    ...(isAdmin
        ? [
              {
                  title: 'Gestión usuarios',
                  href: '/gestion-usuarios',
                  icon: Users,
              },
          ]
        : []),
    ...(isAdmin
        ? [
              {
                  title: 'Áreas',
                  href: '/areas',
                  icon: Building2,
              },
          ]
        : []),
    ...(isAdmin
        ? [
              {
                  title: 'Reportes',
                  href: '/reportes',
                  icon: FileBarChart,
              },
          ]
        : []),
    {
        title: 'Documentos',
        href: '/documentos',
        icon: FileText,
    },
    ...(isAdmin
        ? [
              {
                  title: 'Configuración de la Empresa',
                  href: '/settings/company',
                  icon: Settings,
              },
          ]
        : []),
    ...(isSuperAdmin
        ? [
              {
                  title: 'Gestión del Sistema',
                  href: '/gestion-sistema',
                  icon: Cog,
              },
          ]
        : []),
];

const footerNavItems: NavItem[] = [
    //
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/dashboard">
                            <div class="flex items-center gap-2">
                                <div class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-md border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
                                    <img
                                        :src="companyLogoUrl"
                                        :alt="`Logo de ${companyName}`"
                                        class="h-full w-full object-contain p-1"
                                    />
                                </div>
                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ companyName }}</span>
                                    <span class="truncate text-xs text-slate-500 dark:text-slate-400">
                                        Menú de empresa
                                    </span>
                                </div>
                            </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
