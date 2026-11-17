<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    Building2,
    CalendarDays,
    ClipboardCheck,
    ClipboardList,
    Clock,
    Cog,
    FileText,
    History,
    LayoutDashboard,
    Layers,
    Settings,
    Tags,
    Timer,
    UserCog,
    UsersRound,
} from 'lucide-vue-next';
import { computed } from 'vue';
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
import type { NavGroup, NavItem } from '@/types';

const page = usePage();
const user = computed(() => page.props.auth?.user as any);
const isAdmin = computed(
    () => user.value?.role === 'admin' || user.value?.role === 'superadmin',
);
const isSuperAdmin = computed(() => user.value?.role === 'superadmin');
const superAdminContext = computed(
    () => (page.props as any).super_admin_context,
);
const isGlobalContext = computed(
    () =>
        isSuperAdmin.value && !superAdminContext.value?.impersonated_user_id,
);
const enabledModules = computed<string[]>(
    () => (page.props as any).enabled_modules ?? [],
);

const company = computed(() => (page.props as any).company ?? {});
const companyName = computed(() => company.value?.name || 'Empresa');
const companyLogoUrl = computed(() => company.value?.logo_url || '/logo.png');

const can = (moduleSlug: string): boolean =>
    enabledModules.value.includes(moduleSlug);

const homeHref = computed(() =>
    isGlobalContext.value ? '/superadmin/dashboard' : '/dashboard',
);

const compact = (items: Array<NavItem | false>): NavItem[] =>
    items.filter(Boolean) as NavItem[];

const navGroups = computed<NavGroup[]>(() => {
    // SuperAdmin en contexto global
    if (isGlobalContext.value) {
        return [
            {
                label: 'Plataforma',
                items: [
                    {
                        title: 'Resumen global',
                        href: '/superadmin/dashboard',
                        icon: LayoutDashboard,
                    },
                    {
                        title: 'Empresas (tenants)',
                        href: '/superadmin/tenants',
                        icon: Building2,
                    },
                    {
                        title: 'Planes y módulos',
                        href: '/admin/gestion-sistema/planes',
                        icon: Layers,
                    },
                    {
                        title: 'Auditoría',
                        href: '/superadmin/audit',
                        icon: History,
                    },
                    {
                        title: 'Configuración global',
                        href: '/gestion-sistema',
                        icon: Cog,
                    },
                ],
            },
        ];
    }

    const groups: NavGroup[] = [
        {
            label: 'General',
            items: compact([
                can('dashboard') && {
                    title: 'Inicio',
                    href: '/dashboard',
                    icon: LayoutDashboard,
                },
                can('calendario') && {
                    title: 'Calendario',
                    href: '/calendario',
                    icon: CalendarDays,
                },
                can('comunidad') && {
                    title: 'Comunidad',
                    href: '/comunidad',
                    icon: UsersRound,
                },
                can('horas-extra') && {
                    title: 'Mis horas extra',
                    href: '/overtime-hours',
                    icon: Clock,
                },
                (isAdmin.value || Boolean(user.value?.is_area_manager)) && {
                    title: 'Aprobaciones',
                    href: '/aprobaciones',
                    icon: ClipboardCheck,
                },
            ]),
        },
    ];

    if (isAdmin.value) {
        groups.push({
            label: 'Gestión',
            items: compact([
                can('gestion-usuarios') && {
                    title: 'Usuarios y vacaciones',
                    href: '/gestion-usuarios',
                    icon: UserCog,
                },
                can('areas') && {
                    title: 'Áreas',
                    href: '/areas',
                    icon: Building2,
                },
                can('solicitudes') && {
                    title: 'Solicitudes',
                    href: '/solicitudes',
                    icon: ClipboardList,
                },
                can('horas-extra') && {
                    title: 'Horas extra del equipo',
                    href: '/admin/overtime-hours',
                    icon: Timer,
                },
                can('documentos') && {
                    title: 'Documentos',
                    href: '/documentos',
                    icon: FileText,
                },
                can('reportes') && {
                    title: 'Reportes',
                    href: '/reportes',
                    icon: BarChart3,
                },
            ]),
        });

        groups.push({
            label: 'Configuración',
            items: compact([
                {
                    title: 'Tipos de ausencia',
                    href: '/admin/absence-types',
                    icon: Tags,
                },
                can('configuracion-empresa') && {
                    title: 'Empresa',
                    href: '/settings/company',
                    icon: Settings,
                },
                isSuperAdmin.value && {
                    title: 'Gestión del sistema',
                    href: '/gestion-sistema',
                    icon: Cog,
                },
            ]),
        });
    }

    return groups.filter((g) => g.items.length > 0);
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="homeHref">
                            <div
                                class="flex size-8 shrink-0 items-center justify-center overflow-hidden rounded-md border bg-card"
                            >
                                <img
                                    :src="companyLogoUrl"
                                    :alt="`Logo de ${companyName}`"
                                    class="size-full object-contain p-0.5"
                                />
                            </div>
                            <div
                                class="grid flex-1 text-left text-sm leading-tight"
                            >
                                <span class="truncate font-semibold">{{
                                    companyName
                                }}</span>
                                <span
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{
                                        isGlobalContext
                                            ? 'Superadministración'
                                            : 'Ausentra'
                                    }}
                                </span>
                            </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :groups="navGroups" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
