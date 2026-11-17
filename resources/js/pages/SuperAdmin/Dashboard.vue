<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Building2,
    CheckCircle2,
    ChevronRight,
    Clock,
    LayoutDashboard,
    UsersRound,
} from 'lucide-vue-next';
import EmptyState from '@/components/app/EmptyState.vue';
import PageContainer from '@/components/app/PageContainer.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import SectionCard from '@/components/app/SectionCard.vue';
import StatCard from '@/components/app/StatCard.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';

interface Stats {
    total_tenants: number;
    active_tenants: number;
    inactive_tenants: number;
    total_users: number;
    total_admins: number;
    total_absences: number;
    pending_absences: number;
}

interface Tenant {
    id: number;
    name: string;
    slug: string;
    domain: string;
    is_active: boolean;
    created_at: string;
    admin_name: string;
}

defineProps<{
    stats: Stats;
    recentTenants: Tenant[];
}>();

const shortcuts = [
    {
        href: '/superadmin/tenants',
        title: 'Empresas',
        description: 'Crear, activar y administrar tenants',
    },
    {
        href: '/superadmin/audit',
        title: 'Auditoría',
        description: 'Historial de acciones en la plataforma',
    },
    {
        href: '/gestion-sistema',
        title: 'Configuración global',
        description: 'Planes, suscripciones y anuncios',
    },
];
</script>

<template>
    <Head title="Resumen global" />
    <AppLayout>
        <PageContainer>
            <PageHeader
                title="Resumen global"
                description="Estado de todas las empresas (tenants) de la plataforma."
                :icon="LayoutDashboard"
            >
                <template #actions>
                    <Button as-child>
                        <Link href="/superadmin/tenants">
                            <Building2 /> Gestionar empresas
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <StatCard
                    label="Empresas"
                    :value="stats.total_tenants"
                    :icon="Building2"
                    tone="primary"
                    :hint="`${stats.inactive_tenants ?? 0} inactivas`"
                />
                <StatCard
                    label="Empresas activas"
                    :value="stats.active_tenants"
                    :icon="CheckCircle2"
                    tone="success"
                />
                <StatCard
                    label="Usuarios"
                    :value="stats.total_users"
                    :icon="UsersRound"
                    tone="info"
                    :hint="`${stats.total_admins ?? 0} administradores`"
                />
                <StatCard
                    label="Ausencias pendientes"
                    :value="stats.pending_absences"
                    :icon="Clock"
                    tone="warning"
                    :hint="`${stats.total_absences ?? 0} registradas en total`"
                />
            </div>

            <div class="grid gap-3 md:grid-cols-3">
                <Link
                    v-for="item in shortcuts"
                    :key="item.href"
                    :href="item.href"
                    class="group flex items-center justify-between gap-3 rounded-xl border bg-card px-4 py-3 transition-colors hover:border-primary/40 hover:bg-accent/50 focus-visible:ring-[3px] focus-visible:ring-ring/40 focus-visible:outline-none"
                >
                    <span class="min-w-0">
                        <span class="block text-sm font-medium">{{
                            item.title
                        }}</span>
                        <span
                            class="block truncate text-xs text-muted-foreground"
                            >{{ item.description }}</span
                        >
                    </span>
                    <ChevronRight
                        class="size-4 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5"
                        aria-hidden="true"
                    />
                </Link>
            </div>

            <SectionCard title="Últimas empresas registradas" flush>
                <template #actions>
                    <Button variant="ghost" size="sm" as-child>
                        <Link href="/superadmin/tenants">
                            Ver todas <ChevronRight />
                        </Link>
                    </Button>
                </template>

                <EmptyState
                    v-if="recentTenants.length === 0"
                    :icon="Building2"
                    compact
                    title="Aún no hay empresas"
                    description="Cuando se registre una empresa aparecerá aquí."
                />
                <div v-else class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Empresa</th>
                                <th>Dominio</th>
                                <th>Administrador</th>
                                <th>Estado</th>
                                <th class="text-right">
                                    <span class="sr-only">Acciones</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="tenant in recentTenants" :key="tenant.id">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex size-8 shrink-0 items-center justify-center rounded-md bg-primary-soft text-sm font-semibold text-primary"
                                            aria-hidden="true"
                                        >
                                            {{
                                                tenant.name
                                                    .charAt(0)
                                                    .toUpperCase()
                                            }}
                                        </div>
                                        <span class="font-medium">{{
                                            tenant.name
                                        }}</span>
                                    </div>
                                </td>
                                <td class="text-muted-foreground">
                                    {{ tenant.domain || '—' }}
                                </td>
                                <td>{{ tenant.admin_name || '—' }}</td>
                                <td>
                                    <StatusBadge
                                        :status="
                                            tenant.is_active
                                                ? 'activo'
                                                : 'inactivo'
                                        "
                                    />
                                </td>
                                <td class="text-right">
                                    <Button variant="ghost" size="sm" as-child>
                                        <Link
                                            :href="`/superadmin/tenants/${tenant.id}`"
                                            :aria-label="`Ver ${tenant.name}`"
                                        >
                                            Ver detalle
                                        </Link>
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </SectionCard>
        </PageContainer>
    </AppLayout>
</template>
