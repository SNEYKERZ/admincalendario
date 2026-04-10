<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    Plus,
    Search,
    Edit2,
    Trash2,
    X,
    Building2,
    Users,
    ChevronRight,
} from 'lucide-vue-next';

interface Area {
    id: number;
    name: string;
    description: string | null;
    color: string;
    display_order: number;
    is_active: boolean;
    employee_count: number;
    created_at: string;
}

const toast = useToast();

const areas = ref<Area[]>([]);
const loading = ref(false);
const showModal = ref(false);
const modalMode = ref<'create' | 'edit'>('create');
const selectedArea = ref<Area | null>(null);

const search = ref('');
const filterActive = ref('all');

const form = ref({
    name: '',
    description: '',
    color: '#3B82F6',
    display_order: 0,
    is_active: true,
});

const filteredAreas = computed(() => {
    return areas.value.filter((a) => {
        const matchesSearch =
            !search.value ||
            a.name.toLowerCase().includes(search.value.toLowerCase());
        const matchesActive =
            filterActive.value === 'all' ||
            (filterActive.value === 'active' && a.is_active) ||
            (filterActive.value === 'inactive' && !a.is_active);
        return matchesSearch && matchesActive;
    });
});

const loadAreas = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/api/areas', {
            params: { active: filterActive.value },
        });
        areas.value = res.data.areas || [];
    } catch (e) {
        console.error(e);
        toast.error('Error cargando áreas');
    } finally {
        loading.value = false;
    }
};

const openCreate = () => {
    modalMode.value = 'create';
    form.value = {
        name: '',
        description: '',
        color: '#3B82F6',
        display_order: 0,
        is_active: true,
    };
    selectedArea.value = null;
    showModal.value = true;
};

const openEdit = (area: Area) => {
    modalMode.value = 'edit';
    form.value = {
        name: area.name,
        description: area.description || '',
        color: area.color,
        display_order: area.display_order,
        is_active: area.is_active,
    };
    selectedArea.value = area;
    showModal.value = true;
};

const saveArea = async () => {
    try {
        if (modalMode.value === 'create') {
            await axios.post('/api/areas', form.value);
            toast.success('Área creada exitosamente');
        } else {
            await axios.put(`/api/areas/${selectedArea.value?.id}`, form.value);
            toast.success('Área actualizada exitosamente');
        }
        showModal.value = false;
        loadAreas();
    } catch (e: any) {
        console.error(e);
        toast.error(e.response?.data?.message || 'Error guardando área');
    }
};

const deleteArea = async (area: Area) => {
    if (!confirm(`¿Eliminar el área "${area.name}"?`)) return;

    try {
        await axios.delete(`/api/areas/${area.id}`);
        toast.success('Área eliminada');
        loadAreas();
    } catch (e: any) {
        console.error(e);
        toast.error(e.response?.data?.message || 'Error eliminando área');
    }
};

const toggleActive = async (area: Area) => {
    try {
        await axios.put(`/api/areas/${area.id}`, {
            is_active: !area.is_active,
        });
        loadAreas();
    } catch (e) {
        console.error(e);
        toast.error('Error cambiando estado');
    }
};

onMounted(() => {
    loadAreas();
});
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-6">
            <!-- Header -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">
                        Áreas Organizacionales
                    </h1>
                    <p class="mt-1 text-slate-500">
                        Gestiona las áreas de tu organización
                    </p>
                </div>
                <button
                    @click="openCreate"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700"
                >
                    <Plus class="h-4 w-4" />
                    Nueva Área
                </button>
            </div>

            <!-- Filters -->
            <div class="flex flex-col gap-4 sm:flex-row">
                <div class="relative max-w-md flex-1">
                    <Search
                        class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400"
                    />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Buscar áreas..."
                        class="w-full rounded-lg border border-slate-200 py-2 pr-4 pl-10 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    />
                </div>
                <select
                    v-model="filterActive"
                    class="rounded-lg border border-slate-200 px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                >
                    <option value="all">Todos los estados</option>
                    <option value="active">Activas</option>
                    <option value="inactive">Inactivas</option>
                </select>
            </div>

            <!-- Areas Grid -->
            <div v-if="loading" class="py-12 text-center text-slate-500">
                Cargando áreas...
            </div>

            <div
                v-else-if="filteredAreas.length === 0"
                class="py-12 text-center text-slate-500"
            >
                <Building2 class="mx-auto mb-4 h-12 w-12 text-slate-300" />
                <p>No hay áreas registradas</p>
                <button
                    @click="openCreate"
                    class="mt-4 text-blue-600 hover:underline"
                >
                    Crear primera área
                </button>
            </div>

            <div
                v-else
                class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
            >
                <div
                    v-for="area in filteredAreas"
                    :key="area.id"
                    class="rounded-xl border border-slate-200 bg-white p-4 transition-shadow hover:shadow-md"
                >
                    <div class="mb-3 flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-lg"
                                :style="{ backgroundColor: area.color + '20' }"
                            >
                                <Building2
                                    class="h-5 w-5"
                                    :style="{ color: area.color }"
                                />
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-900">
                                    {{ area.name }}
                                </h3>
                                <span
                                    :class="
                                        area.is_active
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-slate-100 text-slate-500'
                                    "
                                    class="rounded-full px-2 py-0.5 text-xs"
                                >
                                    {{ area.is_active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </div>
                        </div>
                        <div class="flex gap-1">
                            <button
                                @click="openEdit(area)"
                                class="rounded-lg p-1.5 text-slate-400 hover:bg-blue-50 hover:text-blue-600"
                            >
                                <Edit2 class="h-4 w-4" />
                            </button>
                            <button
                                @click="deleteArea(area)"
                                class="rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <p
                        v-if="area.description"
                        class="mb-3 line-clamp-2 text-sm text-slate-500"
                    >
                        {{ area.description }}
                    </p>

                    <div class="flex items-center gap-2 text-sm text-slate-500">
                        <Users class="h-4 w-4" />
                        <span>{{ area.employee_count }} empleados</span>
                    </div>

                    <button
                        @click="toggleActive(area)"
                        class="mt-3 w-full rounded-lg border border-slate-200 py-1.5 text-sm text-slate-600 transition-colors hover:bg-slate-50"
                    >
                        {{ area.is_active ? 'Desactivar' : 'Activar' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div class="w-full max-w-md rounded-xl bg-white">
                <div class="flex items-center justify-between border-b p-4">
                    <h2 class="text-lg font-semibold">
                        {{
                            modalMode === 'create'
                                ? 'Nueva Área'
                                : 'Editar Área'
                        }}
                    </h2>
                    <button
                        @click="showModal = false"
                        class="rounded p-1 hover:bg-slate-100"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="saveArea" class="space-y-4 p-4">
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-slate-700"
                        >
                            Nombre *
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            placeholder="Ej: Desarrollo, Marketing, Ventas"
                        />
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-slate-700"
                        >
                            Descripción
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            placeholder="Descripción opcional del área"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-slate-700"
                            >
                                Color
                            </label>
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="form.color"
                                    type="color"
                                    class="h-10 w-10 cursor-pointer rounded border border-slate-200"
                                />
                                <span class="text-sm text-slate-500">{{
                                    form.color
                                }}</span>
                            </div>
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-slate-700"
                            >
                                Orden
                            </label>
                            <input
                                v-model.number="form.display_order"
                                type="number"
                                min="0"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            />
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            id="is_active"
                            class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        />
                        <label for="is_active" class="text-sm text-slate-700">
                            Área activa
                        </label>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="flex-1 rounded-lg border border-slate-200 px-4 py-2 text-slate-600 hover:bg-slate-50"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
                        >
                            {{ modalMode === 'create' ? 'Crear' : 'Guardar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
