<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    Building2,
    FolderGit2,
    LayoutDashboard,
    LayoutGrid,
    Users,
    FileBarChart,
    FileText,
    Settings,
    Cog,
    Menu,
    X,
} from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
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
import { ref } from 'vue';

const page = usePage();
const user = page.props.auth?.user;
const isAdmin = user?.role === 'admin' || user?.role === 'superadmin';
const isSuperAdmin = user?.role === 'superadmin';

// Mobile menu state
const mobileMenuOpen = ref(false);

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

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
    ...(isAdmin
        ? [
              {
                  title: 'Documentos',
                  href: '/documentos',
                  icon: FileText,
              },
          ]
        : []),
    ...(isAdmin
        ? [
              {
                  title: 'Configuración',
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
    /* {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },*/
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/dashboard">
                            <AppLogo />
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
