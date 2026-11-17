<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { refDebounced } from '@vueuse/core';
import axios from 'axios';
import { Mail, Phone, RefreshCw, Search, UsersRound } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import EmptyState from '@/components/app/EmptyState.vue';
import PageContainer from '@/components/app/PageContainer.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { useNotification } from '@/composables/useNotification';
import AppLayout from '@/layouts/AppLayout.vue';
import { includesNormalized } from '@/lib/search';
import type { StatusTone } from '@/lib/status';

interface CommunityUser {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    photo_url: string | null;
    role: string;
    role_label: string;
    area: string | null;
    area_id: number | null;
    age: number | null;
    status: 'pendiente' | 'Ausente' | 'disponible';
}

interface Area {
    id: number;
    name: string;
}

const notify = useNotification();
const loading = ref(false);
const failed = ref(false);
const users = ref<CommunityUser[]>([]);
const areas = ref<Area[]>([]);
const search = ref('');
const debouncedSearch = refDebounced(search, 200);
const selectedArea = ref<number | null>(null);
const statusFilter = ref<'all' | 'disponible' | 'Ausente' | 'pendiente'>('all');

const loadCommunity = async () => {
    loading.value = true;
    failed.value = false;
    try {
        const response = await axios.get('/comunidad/data');
        users.value = response.data.users ?? [];
        areas.value = response.data.areas ?? [];
    } catch (error) {
        console.error('Error loading community data:', error);
        failed.value = true;
        notify.error('No se pudo cargar la comunidad.');
    } finally {
        loading.value = false;
    }
};

const filteredUsers = computed(() =>
    users.value.filter((user) => {
        const term = debouncedSearch.value;
        const matchesSearch =
            includesNormalized(user.name, term) ||
            includesNormalized(user.email, term) ||
            includesNormalized(user.phone, term) ||
            includesNormalized(user.role_label, term) ||
            includesNormalized(user.area, term);
        const matchesArea = !selectedArea.value || user.area_id === selectedArea.value;
        const matchesStatus = statusFilter.value === 'all' || user.status === statusFilter.value;
        return matchesSearch && matchesArea && matchesStatus;
    }),
);

const counts = computed(() => ({
    all: users.value.length,
    disponible: users.value.filter((u) => u.status === 'disponible').length,
    Ausente: users.value.filter((u) => u.status === 'Ausente').length,
    pendiente: users.value.filter((u) => u.status === 'pendiente').length,
}));

const statusMeta = (status: CommunityUser['status']): { label: string; tone: StatusTone } => {
    if (status === 'pendiente') return { label: 'Ausencia pendiente', tone: 'warning' };
    if (status === 'Ausente') return { label: 'Ausente hoy', tone: 'destructive' };
    return { label: 'Disponible', tone: 'success' };
};

const statusTabs = [
    { value: 'all', label: 'Todos' },
    { value: 'disponible', label: 'Disponibles' },
    { value: 'Ausente', label: 'Ausentes' },
    { value: 'pendiente', label: 'Con pendientes' },
] as const;

const hasFilters = computed(() => !!search.value || !!selectedArea.value || statusFilter.value !== 'all');

const clearFilters = () => {
    search.value = '';
    selectedArea.value = null;
    statusFilter.value = 'all';
};

const initials = (name: string) => {
    const parts = name.trim().split(/\s+/).filter(Boolean);
    if (parts.length === 0) return 'U';
    if (parts.length === 1) return parts[0][0]?.toUpperCase() ?? 'U';
    return `${parts[0][0] ?? ''}${parts[1][0] ?? ''}`.toUpperCase();
};

onMounted(loadCommunity);
</script>

<template>
    <Head title="Comunidad" />
    <AppLayout>
        <PageContainer>
            <PageHeader
                title="Comunidad"
                description="Directorio interno del equipo con la disponibilidad de cada persona hoy."
                :icon="UsersRound"
            />

            <div class="flex flex-col gap-3">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div class="relative w-full sm:max-w-sm">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <Input
                            v-model="search"
                            type="search"
                            class="pl-9"
                            placeholder="Buscar por nombre, correo, teléfono, rol o área"
                            aria-label="Buscar en la comunidad"
                        />
                    </div>
                    <select
                        v-if="areas.length > 1"
                        v-model="selectedArea"
                        class="input sm:w-52"
                        aria-label="Filtrar por área"
                    >
                        <option :value="null">Todas las áreas</option>
                        <option v-for="area in areas" :key="area.id" :value="area.id">
                            {{ area.name }}
                        </option>
                    </select>
                </div>
                <div class="-mx-1 flex gap-1 overflow-x-auto px-1 pb-1" role="tablist" aria-label="Filtrar por disponibilidad">
                    <button
                        v-for="tab in statusTabs"
                        :key="tab.value"
                        type="button"
                        role="tab"
                        :aria-selected="statusFilter === tab.value"
                        class="inline-flex h-8 shrink-0 items-center gap-1.5 rounded-md border px-3 text-[13px] font-medium transition-colors"
                        :class="
                            statusFilter === tab.value
                                ? 'border-primary/30 bg-primary-soft text-primary'
                                : 'bg-card text-muted-foreground hover:text-foreground'
                        "
                        @click="statusFilter = tab.value"
                    >
                        {{ tab.label }}
                        <span class="text-xs tabular-nums opacity-80">{{ counts[tab.value] }}</span>
                    </button>
                </div>
            </div>

            <div v-if="loading" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                <div v-for="n in 8" :key="n" class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <Skeleton class="size-12 rounded-full" />
                    <div class="flex-1 space-y-2">
                        <Skeleton class="h-4 w-3/4" />
                        <Skeleton class="h-3 w-1/2" />
                    </div>
                </div>
            </div>

            <div v-else-if="failed" class="rounded-xl border bg-card">
                <EmptyState
                    :icon="UsersRound"
                    title="No se pudo cargar la comunidad"
                    description="Revisa tu conexión e inténtalo de nuevo."
                >
                    <Button variant="outline" size="sm" @click="loadCommunity"><RefreshCw /> Reintentar</Button>
                </EmptyState>
            </div>

            <div v-else-if="filteredUsers.length === 0" class="rounded-xl border bg-card">
                <EmptyState
                    :icon="UsersRound"
                    :title="hasFilters ? 'No hay personas con esos criterios' : 'Aún no hay personas en la comunidad'"
                    :description="hasFilters ? 'Prueba con otro término o quita los filtros.' : undefined"
                >
                    <Button v-if="hasFilters" variant="outline" size="sm" @click="clearFilters">Limpiar filtros</Button>
                </EmptyState>
            </div>

            <ul v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                <li v-for="user in filteredUsers" :key="user.id" class="flex flex-col gap-3 rounded-xl border bg-card p-4">
                    <div class="flex items-start gap-3">
                        <img
                            v-if="user.photo_url"
                            :src="user.photo_url"
                            :alt="`Foto de ${user.name}`"
                            class="size-12 shrink-0 rounded-full object-cover"
                            loading="lazy"
                        />
                        <div
                            v-else
                            class="flex size-12 shrink-0 items-center justify-center rounded-full bg-muted text-sm font-semibold text-muted-foreground"
                            aria-hidden="true"
                        >
                            {{ initials(user.name) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <h2 class="truncate text-[15px] font-semibold" :title="user.name">{{ user.name }}</h2>
                            <p class="truncate text-[13px] text-muted-foreground">
                                {{ user.role_label }} · {{ user.area || 'Sin área' }}
                            </p>
                            <StatusBadge
                                class="mt-1.5"
                                :label="statusMeta(user.status).label"
                                :tone="statusMeta(user.status).tone"
                                :dot="true"
                            />
                        </div>
                    </div>
                    <dl class="mt-auto grid gap-1.5 border-t pt-3 text-[13px]">
                        <div class="flex min-w-0 items-center gap-2">
                            <dt class="sr-only">Correo</dt>
                            <Mail class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />
                            <dd class="min-w-0 truncate">
                                <a :href="`mailto:${user.email}`" class="hover:text-primary hover:underline">{{ user.email }}</a>
                            </dd>
                        </div>
                        <div class="flex items-center gap-2">
                            <dt class="sr-only">Teléfono</dt>
                            <Phone class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />
                            <dd>
                                <a v-if="user.phone" :href="`tel:${user.phone}`" class="hover:text-primary hover:underline">{{ user.phone }}</a>
                                <span v-else class="text-muted-foreground">No registrado</span>
                            </dd>
                        </div>
                        <div v-if="user.age" class="text-xs text-muted-foreground">
                            <dt class="sr-only">Edad</dt>
                            <dd>{{ user.age }} años</dd>
                        </div>
                    </dl>
                </li>
            </ul>
        </PageContainer>
    </AppLayout>
</template>
