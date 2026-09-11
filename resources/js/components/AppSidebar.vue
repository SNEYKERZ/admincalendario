<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Building2, LayoutDashboard, LayoutGrid, Users, FileBarChart, FileText, Settings, Cog, Clock, FileCheck } from 'lucide-vue-next';
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
const superAdminContext = computed(() => (page.props as any).super_admin_context);
const isGlobalContext = computed(() => isSuperAdmin && !superAdminContext.value?.impersonated_user_id);
const enabledModules = computed(() => (page.props as any).enabled_modules ?? []);

const company = computed(() => (page.props as any).company ?? {});
const companyName = computed(() => company.value?.name || 'Empresa');
const companyLogoUrl = computed(() => company.value?.logo_url || '/logo.png');

const canAccessModule = (moduleSlug: string): boolean => {
    return enabledModules.value.includes(moduleSlug);
};

const mainNavItems = computed<NavItem[]>(() => {
    // Si es SuperAdmin en contexto global, mostrar opciones de SuperAdmin
    if (isGlobalContext.value) {
        return [
            {
                title: 'Dashboard Global',
                href: '/superadmin/dashboard',
                icon: LayoutDashboard,
            },
            {
                title: 'Gestionar Tenants',
                href: '/superadmin/tenants',
                icon: Building2,
            },
            {
                title: 'Gestión de Planes',
                href: '/admin/gestion-sistema/planes',
                icon: FileCheck,
            },
            {
                title: 'Auditoría',
                href: '/superadmin/audit',
                icon: FileBarChart,
            },
            {
                title: 'Configuración Global',
                href: '/gestion-sistema',
                icon: Cog,
            },
        ];
    }

    // Opciones normales para Admin/Colaborador
    return [
        ...(canAccessModule('dashboard')
            ? [
                  {
                      title: 'Dashboard',
                      href: '/dashboard',
                      icon: LayoutDashboard,
                  },
              ]
            : []),
        ...(canAccessModule('calendario')
            ? [
                  {
                      title: 'Calendario',
                      href: '/calendario',
                      icon: LayoutGrid,
                  },
              ]
            : []),
        ...(canAccessModule('comunidad')
            ? [
                  {
                      title: 'Comunidad',
                      href: '/comunidad',
                      icon: Users,
                  },
              ]
            : []),
        ...(isAdmin && canAccessModule('gestion-usuarios')
            ? [
                  {
                      title: 'Gestión usuarios',
                      href: '/gestion-usuarios',
                      icon: Users,
                  },
              ]
            : []),
        ...(isAdmin && canAccessModule('areas')
            ? [
                  {
                      title: 'Áreas',
                      href: '/areas',
                      icon: Building2,
                  },
              ]
            : []),
        ...(isAdmin && canAccessModule('reportes')
            ? [
                  {
                      title: 'Reportes',
                      href: '/reportes',
                      icon: FileBarChart,
                  },
              ]
            : []),
        ...(canAccessModule('horas-extra')
            ? [
                  {
                      title: 'Horas Extra',
                      href: '/overtime-hours',
                      icon: Clock,
                  },
              ]
            : []),
        ...(isAdmin && canAccessModule('horas-extra')
            ? [
                  {
                      title: 'Admin Horas Extra',
                      href: '/admin/overtime-hours',
                      icon: Clock,
                  },
              ]
            : []),
        ...(isAdmin
            ? [
                  {
                      title: 'Tipos de Ausencia',
                      href: '/admin/absence-types',
                      icon: FileCheck,
                  },
              ]
            : []),
        ...(isAdmin && canAccessModule('solicitudes')
            ? [
                  {
                      title: 'Solicitudes de Empleados',
                      href: '/solicitudes',
                      icon: FileCheck,
                  },
              ]
            : []),
        ...(isAdmin && canAccessModule('documentos')
            ? [
                  {
                      title: 'Documentos',
                      href: '/documentos',
                      icon: FileText,
                  },
              ]
            : []),
        ...(isAdmin && canAccessModule('configuracion-empresa')
            ? [
                  {
                      title: 'Configuración de la Empresa',
                      href: '/settings/company',
                      icon: Settings,
                  },
              ]
            : []),
        ...(isSuperAdmin && !isGlobalContext.value
            ? [
                  {
                      title: 'Gestión del Sistema',
                      href: '/gestion-sistema',
                      icon: Cog,
                  },
              ]
            : []),
    ];
});

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

