<script setup lang="ts">
import {
    Building2,
    CalendarCheck,
    CalendarClock,
    CalendarX2,
    Clock,
    Hourglass,
    ShieldCheck,
    Sun,
    UserCheck,
    Users,
} from 'lucide-vue-next';
import StatCard from '@/components/app/StatCard.vue';
import type {
    MetricOrganization,
    MetricPersonal,
    MetricSuperadmin,
} from './types';


defineProps<{
    personal: MetricPersonal | null;
    organization: MetricOrganization | null;
    superadmin: MetricSuperadmin | null;
    isAdmin: boolean;
    isSuperAdmin: boolean;
    loading: boolean;
}>();
</script>

<template>
    <div class="space-y-6">
        <!-- Equipo: lo primero que necesita un administrador -->
        <section
            v-if="isAdmin && (organization || loading)"
            aria-labelledby="metrics-team"
            class="space-y-3"
        >
            <h2
                id="metrics-team"
                class="text-[13px] font-medium text-muted-foreground"
            >
                Equipo
            </h2>
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <StatCard
                    label="Colaboradores"
                    :value="organization?.total_employees"
                    :icon="Users"
                    :loading="loading && !organization"
                />
                <StatCard
                    label="Pendientes de aprobar"
                    :value="organization?.pending_absences"
                    :icon="Hourglass"
                    tone="warning"
                    :loading="loading && !organization"
                />
                <StatCard
                    label="No disponibles hoy"
                    :value="organization?.unavailable_now"
                    :icon="CalendarX2"
                    tone="destructive"
                    :loading="loading && !organization"
                />
                <StatCard
                    label="Aprobadas este mes"
                    :value="organization?.approved_this_month"
                    :icon="CalendarCheck"
                    tone="success"
                    :loading="loading && !organization"
                />
            </div>
        </section>

        <section aria-labelledby="metrics-personal" class="space-y-3">
            <h2
                id="metrics-personal"
                class="text-[13px] font-medium text-muted-foreground"
            >
                {{ isAdmin ? 'Mis datos' : 'Resumen' }}
            </h2>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-5">
                <StatCard
                    label="Días disponibles"
                    :value="personal?.available_vacation_days ?? 0"
                    :icon="Sun"
                    tone="primary"
                    :loading="loading && !personal"
                />
                <StatCard
                    label="Días usados (año)"
                    :value="personal?.used_vacation_days ?? 0"
                    :icon="CalendarCheck"
                    :loading="loading && !personal"
                />
                <StatCard
                    label="Pendientes"
                    :value="personal?.pending_absences ?? 0"
                    :icon="Clock"
                    tone="warning"
                    :loading="loading && !personal"
                />
                <StatCard
                    label="Aprobadas este mes"
                    :value="personal?.approved_this_month ?? 0"
                    :icon="UserCheck"
                    tone="success"
                    :loading="loading && !personal"
                />
                <StatCard
                    label="Vencen en 30 días"
                    :value="personal?.upcoming_expirations ?? 0"
                    :icon="CalendarClock"
                    :tone="
                        (personal?.upcoming_expirations ?? 0) > 0
                            ? 'destructive'
                            : undefined
                    "
                    :loading="loading && !personal"
                />
            </div>
        </section>

        <section
            v-if="isSuperAdmin && superadmin"
            aria-labelledby="metrics-platform"
            class="space-y-3"
        >
            <h2
                id="metrics-platform"
                class="text-[13px] font-medium text-muted-foreground"
            >
                Plataforma
            </h2>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-5">
                <StatCard
                    label="Admins activos"
                    :value="superadmin.total_admins"
                    :icon="ShieldCheck"
                />
                <StatCard
                    label="Superadmins"
                    :value="superadmin.total_superadmins"
                    :icon="ShieldCheck"
                />
                <StatCard
                    label="Empresas activas"
                    :value="superadmin.active_tenants"
                    :icon="Building2"
                />
                <StatCard
                    label="Suscripciones activas"
                    :value="superadmin.active_subscriptions"
                    :icon="CalendarCheck"
                    tone="success"
                />
                <StatCard
                    label="Vencen en 30 días"
                    :value="superadmin.subscriptions_expiring_30d"
                    :icon="CalendarClock"
                    tone="warning"
                />
            </div>
        </section>
    </div>
</template>
