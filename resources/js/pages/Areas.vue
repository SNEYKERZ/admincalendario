<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { refDebounced } from '@vueuse/core';
import axios from 'axios';
import { Building2, MoreHorizontal, Pencil, Plus, Power, Search, Trash2, Users } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import EmptyState from '@/components/app/EmptyState.vue';
import PageContainer from '@/components/app/PageContainer.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import AreaFormDialog from '@/components/areas/AreaFormDialog.vue';
import type { AreaItem } from '@/components/areas/types';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { useConfirm } from '@/composables/useConfirm';
import { useNotification } from '@/composables/useNotification';
import AppLayout from '@/layouts/AppLayout.vue';
import { includesNormalized } from '@/lib/search';

const notify = useNotification();
const confirm = useConfirm();
const page = usePage();
const currentUserId = computed<number | null>(() => (page.props.auth as any)?.user?.id ?? null);

const areas = ref<AreaItem[]>([]);
const loading = ref(false);
const togglingId = ref<number | null>(null);
const showForm = ref(false);
const selectedArea = ref<AreaItem | null>(null);

const search = ref('');
const debouncedSearch = refDebounced(search, 200);
const filterActive = ref<'all' | 'active' | 'inactive'>('all');

const filteredAreas = computed(() =>
    areas.value.filter((a) => {
        const matchesSearch =
            includesNormalized(a.name, debouncedSearch.value) ||
            includesNormalized(a.description, debouncedSearch.value);
        const matchesActive =
            filterActive.value === 'all' ||
            (filterActive.value === 'active' && a.is_active) ||
            (filterActive.value === 'inactive' && !a.is_active);
        return matchesSearch && matchesActive;
    }),
);

const hasFilters = computed(() => !!search.value || filterActive.value !== 'all');

const loadAreas = async () => {
    loading.value = areas.value.length === 0;
    try {
        const res = await axios.get('/api/areas', { params: { active: 'all' } });
        areas.value = res.data.areas || [];
    } catch (e) {
        console.error(e);
        notify.error('No se pudieron cargar las áreas.');
    } finally {
        loading.value = false;
    }
};

const openCreate = () => {
    selectedArea.value = null;
    showForm.value = true;
};

const openEdit = (area: AreaItem) => {
    selectedArea.value = area;
    showForm.value = true;
};

const deleteArea = async (area: AreaItem) => {
    const ok = await confirm({
        title: 'Eliminar área',
        description:
            area.employee_count > 0
                ? `«${area.name}» tiene ${area.employee_count} colaboradores asignados. ¿Eliminarla de todos modos?`
                : `¿Eliminar el área «${area.name}»? Esta acción no se puede deshacer.`,
        confirmText: 'Eliminar área',
        variant: 'destructive',
    });
    if (!ok) return;
    try {
        await axios.delete(`/api/areas/${area.id}`);
        notify.success('Área eliminada');
        await loadAreas();
    } catch (e: any) {
        console.error(e);
        notify.error(e?.response?.data?.message || 'No se pudo eliminar el área.');
    }
};

const toggleActive = async (area: AreaItem) => {
    togglingId.value = area.id;
    try {
        await axios.put(`/api/areas/${area.id}`, { is_active: !area.is_active });
        notify.success(area.is_active ? 'Área desactivada' : 'Área activada');
        await loadAreas();
    } catch (e: any) {
        console.error(e);
        notify.error(e?.response?.data?.message || 'No se pudo cambiar el estado.');
    } finally {
        togglingId.value = null;
    }
};

const clearFilters = () => {
    search.value = '';
    filterActive.value = 'all';
};

onMounted(loadAreas);
</script>

<template>
    <Head title="Áreas" />
    <AppLayout>
        <PageContainer>
            <PageHeader
                title="Áreas"
                description="Organiza a tu equipo por áreas y define quién aprueba sus ausencias."
                :icon="Building2"
            >
                <template #actions>
                    <Button @click="openCreate"><Plus /> Nueva área</Button>
                </template>
            </PageHeader>

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
                        placeholder="Buscar por nombre o descripción"
                        aria-label="Buscar áreas"
                    />
                </div>
                <div
                    class="inline-flex h-9 w-fit items-center rounded-md border bg-card p-0.5 text-[13px]"
                    role="radiogroup"
                    aria-label="Filtrar por estado"
                >
                    <button
                        v-for="opt in [
                            { value: 'all', label: 'Todas' },
                            { value: 'active', label: 'Activas' },
                            { value: 'inactive', label: 'Inactivas' },
                        ] as const"
                        :key="opt.value"
                        type="button"
                        role="radio"
                        :aria-checked="filterActive === opt.value"
                        class="h-full rounded-[5px] px-3 font-medium transition-colors"
                        :class="
                            filterActive === opt.value
                                ? 'bg-primary-soft text-primary'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        @click="filterActive = opt.value"
                    >
                        {{ opt.label }}
                    </button>
                </div>
                <span v-if="!loading" class="text-[13px] text-muted-foreground sm:ml-auto" aria-live="polite">
                    {{ filteredAreas.length }} de {{ areas.length }} áreas
                </span>
            </div>

            <div v-if="loading" class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                <div v-for="n in 6" :key="n" class="space-y-3 rounded-xl border bg-card p-4">
                    <div class="flex items-center gap-3">
                        <Skeleton class="size-9 rounded-lg" />
                        <div class="flex-1 space-y-1.5">
                            <Skeleton class="h-4 w-32" />
                            <Skeleton class="h-3 w-16" />
                        </div>
                    </div>
                    <Skeleton class="h-3 w-full" />
                </div>
            </div>

            <div v-else-if="filteredAreas.length === 0" class="rounded-xl border bg-card">
                <EmptyState
                    :icon="Building2"
                    :title="hasFilters ? 'Ninguna área coincide con los filtros' : 'Aún no hay áreas'"
                    :description="
                        hasFilters
                            ? 'Prueba con otro término o cambia el filtro de estado.'
                            : 'Crea la primera área para agrupar a tus colaboradores.'
                    "
                >
                    <Button v-if="hasFilters" variant="outline" size="sm" @click="clearFilters">Limpiar filtros</Button>
                    <Button v-else size="sm" @click="openCreate"><Plus /> Crear primera área</Button>
                </EmptyState>
            </div>

            <ul v-else class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                <li
                    v-for="area in filteredAreas"
                    :key="area.id"
                    class="flex flex-col gap-3 rounded-xl border bg-card p-4 transition-colors hover:border-input"
                    :class="{ 'opacity-70': !area.is_active }"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex min-w-0 items-center gap-3">
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-lg"
                                :style="{ backgroundColor: `${area.color}1f`, color: area.color }"
                                aria-hidden="true"
                            >
                                <Building2 class="size-[18px]" />
                            </span>
                            <div class="min-w-0">
                                <h2 class="truncate text-[15px] font-semibold">{{ area.name }}</h2>
                                <StatusBadge :status="area.is_active ? 'activo' : 'inactivo'" :label="area.is_active ? 'Activa' : 'Inactiva'" />
                            </div>
                        </div>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="ghost" size="icon-sm" :aria-label="`Acciones para ${area.name}`">
                                    <MoreHorizontal />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-44">
                                <DropdownMenuItem @select="openEdit(area)"><Pencil /> Editar</DropdownMenuItem>
                                <DropdownMenuItem :disabled="togglingId === area.id" @select="toggleActive(area)">
                                    <Power /> {{ area.is_active ? 'Desactivar' : 'Activar' }}
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem variant="destructive" @select="deleteArea(area)">
                                    <Trash2 /> Eliminar
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <p v-if="area.description" class="line-clamp-2 text-sm text-muted-foreground">
                        {{ area.description }}
                    </p>

                    <div class="mt-auto flex items-center justify-between border-t pt-3 text-[13px] text-muted-foreground">
                        <span class="inline-flex items-center gap-1.5">
                            <Users class="size-4" aria-hidden="true" />
                            {{ area.employee_count }} {{ area.employee_count === 1 ? 'colaborador' : 'colaboradores' }}
                        </span>
                        <Button variant="ghost" size="sm" class="-mr-2 h-7" @click="openEdit(area)">Editar</Button>
                    </div>
                </li>
            </ul>
        </PageContainer>

        <AreaFormDialog
            v-model:open="showForm"
            :area="selectedArea"
            :current-user-id="currentUserId"
            @saved="loadAreas"
        />
    </AppLayout>
</template>
