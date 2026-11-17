<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Building2,
    CalendarClock,
    History,
    Layers,
    ShieldCheck,
    UserRoundCog,
    UsersRound,
} from 'lucide-vue-next';
import EmptyState from '@/components/app/EmptyState.vue';
import PageContainer from '@/components/app/PageContainer.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import SectionCard from '@/components/app/SectionCard.vue';
import StatCard from '@/components/app/StatCard.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useConfirm } from '@/composables/useConfirm';
import { useNotification } from '@/composables/useNotification';
import { useSuperAdminContext } from '@/composables/useSuperAdminContext';
import AppLayout from '@/layouts/AppLayout.vue';

interface Tenant {
    id: number;
    name: string;
    slug: string;
    domain: string | null;
    is_active: boolean;
    is_main: boolean;
    timezone: string;
    locale: string;
    created_at: string;
    admin_name: string;
    admin_email: string;
    admin_id: number | null;
}

interface Stats {
    total_users: number;
    admin_count: number;
    collaborator_count: number;
    total_absences: number;
    pending_absences: number;
    approved_absences: number;
    areas_count: number;
}

interface AuditLog {
    id: number;
    action: string;
    description: string | null;
    superadmin_name: string;
    impersonated_name: string | null;
    tenant_name: string | null;
    created_at: string;
}

const props = defineProps<{
    tenant: Tenant;
    stats: Stats;
    auditLog: AuditLog[];
}>();

const { startImpersonation, loading } = useSuperAdminContext();
const confirm = useConfirm();
const notify = useNotification();

const humanize = (action: string) => {
    const text = String(action ?? '').replace(/[_.-]+/g, ' ').trim();
    return text ? text.charAt(0).toUpperCase() + text.slice(1) : '—';
};

const impersonate = async () => {
    if (!props.tenant.admin_id) {
        notify.warning('Esta empresa no tiene un administrador asignado.');
        return;
    }
    const ok = await confirm({
        title: 'Entrar como administrador',
        description: `Verás la aplicación como ${props.tenant.admin_name}. Podrás salir en cualquier momento desde el aviso superior.`,
        confirmText: 'Entrar como admin',
    });
    if (ok) await startImpersonation(props.tenant.admin_id);
};
</script>

<template>
    <Head :title="tenant.name" />
    <AppLayout>
        <PageContainer>
            <PageHeader
                :title="tenant.name"
                description="Detalle de la empresa"
                :icon="Building2"
            >
                <template #actions>
                    <Button variant="outline" as-child>
                        <Link href="/superadmin/tenants">
                            <ArrowLeft /> Volver a empresas
                        </Link>
                    </Button>
                    <Button
                        :disabled="loading || !tenant.admin_id"
                        @click="impersonate"
                    >
                        <Spinner v-if="loading" />
                        <UserRoundCog v-else />
                        Entrar como admin
                    </Button>
                </template>
            </PageHeader>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <StatCard
                    label="Usuarios"
                    :value="stats.total_users"
                    :icon="UsersRound"
                    tone="info"
                    :hint="`${stats.collaborator_count ?? 0} colaboradores`"
                />
                <StatCard
                    label="Administradores"
                    :value="stats.admin_count"
                    :icon="ShieldCheck"
                    tone="primary"
                />
                <StatCard
                    label="Ausencias"
                    :value="stats.total_absences"
                    :icon="CalendarClock"
                    tone="warning"
                    :hint="`${stats.pending_absences ?? 0} pendientes · ${stats.approved_absences ?? 0} aprobadas`"
                />
                <StatCard
                    label="Áreas"
                    :value="stats.areas_count"
                    :icon="Layers"
                />
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <div class="grid content-start gap-4 lg:col-span-2">
                    <SectionCard title="Información general">
                        <dl
                            class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2"
                        >
                            <div>
                                <dt class="text-xs text-muted-foreground">
                                    Nombre
                                </dt>
                                <dd class="mt-0.5 font-medium">
                                    {{ tenant.name }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted-foreground">
                                    Slug
                                </dt>
                                <dd class="mt-0.5 font-mono text-[13px]">
                                    {{ tenant.slug }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted-foreground">
                                    Dominio
                                </dt>
                                <dd class="mt-0.5">
                                    {{
                                        tenant.domain ||
                                        `${tenant.slug}.ausentra.com`
                                    }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted-foreground">
                                    Estado
                                </dt>
                                <dd class="mt-1">
                                    <StatusBadge
                                        :status="
                                            tenant.is_active
                                                ? 'activo'
                                                : 'inactivo'
                                        "
                                    />
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted-foreground">
                                    Zona horaria / idioma
                                </dt>
                                <dd class="mt-0.5">
                                    {{ tenant.timezone || '—' }} ·
                                    {{ tenant.locale || '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted-foreground">
                                    Creada
                                </dt>
                                <dd class="mt-0.5 tabular-nums">
                                    {{ tenant.created_at }}
                                </dd>
                            </div>
                        </dl>
                    </SectionCard>

                    <SectionCard title="Administrador">
                        <div
                            v-if="tenant.admin_id"
                            class="flex items-center gap-3"
                        >
                            <div
                                class="flex size-9 items-center justify-center rounded-full bg-primary-soft text-sm font-semibold text-primary"
                                aria-hidden="true"
                            >
                                {{
                                    (tenant.admin_name || '?')
                                        .charAt(0)
                                        .toUpperCase()
                                }}
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium">
                                    {{ tenant.admin_name }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    {{ tenant.admin_email }}
                                </p>
                            </div>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">
                            Esta empresa no tiene un administrador asignado.
                        </p>
                    </SectionCard>
                </div>

                <SectionCard title="Historial de acciones" flush>
                    <EmptyState
                        v-if="auditLog.length === 0"
                        :icon="History"
                        compact
                        title="Sin historial"
                        description="Aún no hay acciones registradas para esta empresa."
                    />
                    <ol v-else class="max-h-[28rem] divide-y overflow-y-auto">
                        <li
                            v-for="log in auditLog"
                            :key="log.id"
                            class="px-4 py-3 sm:px-5"
                        >
                            <p class="text-[13px] font-medium">
                                {{ humanize(log.action) }}
                            </p>
                            <p
                                v-if="log.description"
                                class="mt-0.5 text-xs text-muted-foreground"
                            >
                                {{ log.description }}
                            </p>
                            <p
                                class="mt-1 text-xs text-muted-foreground tabular-nums"
                            >
                                {{ log.superadmin_name }} · {{ log.created_at }}
                            </p>
                        </li>
                    </ol>
                </SectionCard>
            </div>
        </PageContainer>
    </AppLayout>
</template>
