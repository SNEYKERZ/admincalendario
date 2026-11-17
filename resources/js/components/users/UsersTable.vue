<script setup lang="ts">
import { Eye, Minus, Pencil, Plus, Trash2, UserPlus, Users } from 'lucide-vue-next';
import EmptyState from '@/components/app/EmptyState.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import {
    getInitials,
    roleLabel
    
    
    
} from './types';
import type {ManagedUser, UserArea, UserRole} from './types';

const props = defineProps<{
    users: ManagedUser[];
    areas: UserArea[];
    roles: UserRole[];
    loading: boolean;
    hasFilters: boolean;
    adjustingId: number | null;
}>();

const emit = defineEmits<{
    view: [user: ManagedUser];
    edit: [user: ManagedUser];
    delete: [user: ManagedUser];
    adjust: [user: ManagedUser, days: number];
    create: [];
    clearFilters: [];
}>();

const areaColor = (user: ManagedUser): string | undefined =>
    props.areas.find((a) => a.id === user.area_id)?.color;
</script>

<template>
    <!-- Carga -->
    <div v-if="loading" class="divide-y">
        <div v-for="n in 5" :key="n" class="flex items-center gap-3 px-4 py-3">
            <Skeleton class="size-9 rounded-full" />
            <div class="flex-1 space-y-1.5">
                <Skeleton class="h-3.5 w-40" />
                <Skeleton class="h-3 w-56" />
            </div>
            <Skeleton class="hidden h-5 w-20 sm:block" />
            <Skeleton class="hidden h-5 w-16 sm:block" />
        </div>
    </div>

    <!-- Vacío -->
    <EmptyState
        v-else-if="users.length === 0"
        :icon="Users"
        :title="hasFilters ? 'Ningún usuario coincide con los filtros' : 'Aún no hay usuarios'"
        :description="
            hasFilters
                ? 'Prueba con otro término de búsqueda o quita el filtro de rol.'
                : 'Crea el primer usuario o importa un archivo con tu equipo.'
        "
    >
        <Button v-if="hasFilters" variant="outline" size="sm" @click="emit('clearFilters')">
            Limpiar filtros
        </Button>
        <Button v-else size="sm" @click="emit('create')">
            <UserPlus /> Nuevo usuario
        </Button>
    </EmptyState>

    <template v-else>
        <!-- Escritorio -->
        <div class="hidden overflow-x-auto md:block">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Usuario</th>
                        <th scope="col">Identificación</th>
                        <th scope="col">Rol</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Área</th>
                        <th scope="col" class="text-right">Asignados</th>
                        <th scope="col" class="text-right">Usados</th>
                        <th scope="col" class="text-right">Disponibles</th>
                        <th scope="col" class="text-right">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in users" :key="user.id">
                        <td>
                            <div class="flex min-w-0 items-center gap-3">
                                <img
                                    v-if="user.photo_url"
                                    :src="user.photo_url"
                                    :alt="`Foto de ${user.name}`"
                                    class="size-8 shrink-0 rounded-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex size-8 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-semibold text-muted-foreground"
                                    aria-hidden="true"
                                >
                                    {{ getInitials(user.name) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="truncate font-medium">
                                        {{ user.name }}
                                    </div>
                                    <div class="truncate text-xs text-muted-foreground">
                                        {{ user.email }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="text-muted-foreground">
                            <div>{{ user.identification || '—' }}</div>
                            <div v-if="user.phone" class="text-xs">{{ user.phone }}</div>
                        </td>
                        <td>
                            <StatusBadge
                                :tone="user.role === 'admin' || user.role === 'superadmin' ? 'primary' : 'neutral'"
                                :label="roleLabel(user.role, roles)"
                                :dot="false"
                            />
                        </td>
                        <td>
                            <StatusBadge :status="user.is_active ? 'activo' : 'inactivo'" />
                        </td>
                        <td>
                            <span
                                v-if="user.area_name"
                                class="inline-flex items-center gap-1.5 text-[13px]"
                            >
                                <span
                                    class="size-2 shrink-0 rounded-full bg-muted-foreground"
                                    :style="areaColor(user) ? { backgroundColor: areaColor(user) } : undefined"
                                    aria-hidden="true"
                                />
                                {{ user.area_name }}
                            </span>
                            <span v-else class="text-xs text-muted-foreground">Sin área</span>
                        </td>
                        <td class="text-right tabular-nums">{{ user.allocated }}</td>
                        <td class="text-right tabular-nums">{{ user.used }}</td>
                        <td class="text-right">
                            <div class="inline-flex items-center gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    class="size-7"
                                    :disabled="adjustingId === user.id"
                                    :aria-label="`Restar un día disponible a ${user.name}`"
                                    title="Restar 1 día"
                                    @click="emit('adjust', user, -1)"
                                >
                                    <Minus class="size-3.5" />
                                </Button>
                                <span class="min-w-8 text-center font-semibold text-success tabular-nums">
                                    <Spinner v-if="adjustingId === user.id" class="mx-auto size-3.5" />
                                    <template v-else>{{ user.available }}</template>
                                </span>
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    class="size-7"
                                    :disabled="adjustingId === user.id"
                                    :aria-label="`Sumar un día disponible a ${user.name}`"
                                    title="Sumar 1 día"
                                    @click="emit('adjust', user, 1)"
                                >
                                    <Plus class="size-3.5" />
                                </Button>
                            </div>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-0.5">
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    :aria-label="`Ver detalle de ${user.name}`"
                                    title="Ver detalle"
                                    @click="emit('view', user)"
                                >
                                    <Eye />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    :aria-label="`Editar a ${user.name}`"
                                    title="Editar"
                                    @click="emit('edit', user)"
                                >
                                    <Pencil />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    class="text-destructive hover:bg-destructive-soft hover:text-destructive"
                                    :aria-label="`Eliminar a ${user.name}`"
                                    title="Eliminar"
                                    @click="emit('delete', user)"
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Móvil: tarjetas -->
        <ul class="divide-y md:hidden">
            <li v-for="user in users" :key="user.id" class="space-y-3 px-4 py-3">
                <div class="flex items-start gap-3">
                    <img
                        v-if="user.photo_url"
                        :src="user.photo_url"
                        :alt="`Foto de ${user.name}`"
                        class="size-10 shrink-0 rounded-full object-cover"
                    />
                    <div
                        v-else
                        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-muted text-sm font-semibold text-muted-foreground"
                        aria-hidden="true"
                    >
                        {{ getInitials(user.name) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium">{{ user.name }}</p>
                        <p class="truncate text-xs text-muted-foreground">{{ user.email }}</p>
                        <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                            <StatusBadge
                                :tone="user.role === 'admin' || user.role === 'superadmin' ? 'primary' : 'neutral'"
                                :label="roleLabel(user.role, roles)"
                                :dot="false"
                            />
                            <StatusBadge :status="user.is_active ? 'activo' : 'inactivo'" />
                            <span v-if="user.area_name" class="text-xs text-muted-foreground">
                                · {{ user.area_name }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <div class="text-xs text-muted-foreground tabular-nums">
                        Vacaciones:
                        <span class="font-semibold text-success">{{ user.available }}</span>
                        disp. · {{ user.used }}/{{ user.allocated }} usados
                    </div>
                    <div class="flex items-center gap-0.5">
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            :disabled="adjustingId === user.id"
                            :aria-label="`Restar un día disponible a ${user.name}`"
                            @click="emit('adjust', user, -1)"
                        >
                            <Minus />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            :disabled="adjustingId === user.id"
                            :aria-label="`Sumar un día disponible a ${user.name}`"
                            @click="emit('adjust', user, 1)"
                        >
                            <Plus />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            :aria-label="`Ver detalle de ${user.name}`"
                            @click="emit('view', user)"
                        >
                            <Eye />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            :aria-label="`Editar a ${user.name}`"
                            @click="emit('edit', user)"
                        >
                            <Pencil />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            class="text-destructive hover:bg-destructive-soft hover:text-destructive"
                            :aria-label="`Eliminar a ${user.name}`"
                            @click="emit('delete', user)"
                        >
                            <Trash2 />
                        </Button>
                    </div>
                </div>
            </li>
        </ul>
    </template>
</template>
