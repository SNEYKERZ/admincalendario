<script setup lang="ts">
import { AlertTriangle, Megaphone, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import EmptyState from '@/components/app/EmptyState.vue';
import SectionCard from '@/components/app/SectionCard.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    announcementTypeLabel
    
    
} from './types';
import type {Announcement, AnnouncementAdmin} from './types';

defineProps<{
    announcements: Announcement[];
    announcementAdmins: AnnouncementAdmin[];
}>();
const emit = defineEmits<{
    create: [];
    edit: [announcement: Announcement];
    delete: [announcement: Announcement];
}>();

const typeTone = (type: Announcement['type']) =>
    type === 'expiring' ? 'warning' : type === 'expired' ? 'destructive' : 'info';

const formatDate = (value: string) => {
    const d = new Date(value);
    return Number.isNaN(d.getTime())
        ? value
        : d.toLocaleDateString('es-CO', {
              day: '2-digit',
              month: 'short',
              year: 'numeric',
          });
};
</script>

<template>
    <div class="grid gap-6">
        <SectionCard
            v-if="announcementAdmins.length > 0"
            flush
            class="border-warning/30"
        >
            <template #header>
                <div class="flex items-center gap-2 text-warning">
                    <AlertTriangle class="size-4" aria-hidden="true" />
                    <h2 class="text-[15px] font-semibold">
                        {{ announcementAdmins.length }}
                        {{
                            announcementAdmins.length === 1
                                ? 'administrador necesita'
                                : 'administradores necesitan'
                        }}
                        atención
                    </h2>
                </div>
            </template>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Administrador</th>
                            <th>Plan</th>
                            <th>Vence</th>
                            <th class="text-right">Días restantes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="admin in announcementAdmins" :key="admin.id">
                            <td>
                                <div class="font-medium">{{ admin.name }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ admin.email }}
                                </div>
                            </td>
                            <td>{{ admin.plan_name }}</td>
                            <td class="tabular-nums">
                                {{ formatDate(admin.expires_at) }}
                            </td>
                            <td class="text-right">
                                <StatusBadge
                                    :tone="
                                        admin.days_remaining <= 0
                                            ? 'destructive'
                                            : 'warning'
                                    "
                                    :label="`${admin.days_remaining} días`"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </SectionCard>

        <div class="grid gap-4">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-[15px] font-semibold">Anuncios creados</h2>
                <Button @click="emit('create')">
                    <Plus /> Nuevo anuncio
                </Button>
            </div>

            <div
                v-if="announcements.length === 0"
                class="rounded-xl border bg-card"
            >
                <EmptyState
                    :icon="Megaphone"
                    title="No hay anuncios creados"
                    description="Los anuncios se muestran a los administradores según su tipo y la fecha de vencimiento."
                >
                    <Button variant="outline" @click="emit('create')">
                        <Plus /> Crear anuncio
                    </Button>
                </EmptyState>
            </div>

            <div v-else class="grid gap-4 md:grid-cols-2">
                <article
                    v-for="announcement in announcements"
                    :key="announcement.id"
                    class="flex flex-col rounded-xl border bg-card p-5"
                >
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="font-semibold">{{ announcement.title }}</h3>
                        <StatusBadge
                            :status="
                                announcement.is_active ? 'activo' : 'inactivo'
                            "
                        />
                    </div>
                    <p
                        class="mt-1.5 text-sm whitespace-pre-line text-muted-foreground"
                    >
                        {{ announcement.message }}
                    </p>
                    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                        <StatusBadge
                            :dot="false"
                            :tone="typeTone(announcement.type)"
                            :label="announcementTypeLabel[announcement.type]"
                        />
                        <span
                            v-if="announcement.days_before"
                            class="text-muted-foreground"
                        >
                            {{ announcement.days_before }} días antes
                        </span>
                    </div>
                    <div class="mt-auto flex gap-2 pt-4">
                        <Button
                            variant="outline"
                            size="sm"
                            @click="emit('edit', announcement)"
                        >
                            <Pencil /> Editar
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="text-destructive hover:bg-destructive-soft hover:text-destructive"
                            @click="emit('delete', announcement)"
                        >
                            <Trash2 /> Eliminar
                        </Button>
                    </div>
                </article>
            </div>
        </div>
    </div>
</template>
