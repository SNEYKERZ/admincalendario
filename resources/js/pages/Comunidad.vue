<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { includesNormalized } from '@/lib/search';
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';

interface CommunityUser {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    photo_url: string | null;
    role: string;
    role_label: string;
    area: string | null;
    age: number | null;
    status: 'pendiente' | 'Ausente' | 'disponible';
}

const loading = ref(false);
const users = ref<CommunityUser[]>([]);
const search = ref('');

const loadCommunity = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/comunidad/data');
        users.value = response.data.users ?? [];
    } catch (error) {
        console.error('Error loading community data:', error);
    } finally {
        loading.value = false;
    }
};

const filteredUsers = computed(() => {
    return users.value.filter((user) => {
        return (
            includesNormalized(user.name, search.value) ||
            includesNormalized(user.email, search.value) ||
            includesNormalized(user.phone, search.value) ||
            includesNormalized(user.role_label, search.value) ||
            includesNormalized(user.area, search.value)
        );
    });
});

const statusBadgeClass = (status: CommunityUser['status']) => {
    if (status === 'pendiente') {
        return 'bg-amber-100 text-amber-800 border-amber-200';
    }
    if (status === 'Ausente') {
        return 'bg-rose-100 text-rose-800 border-rose-200';
    }
    return 'bg-emerald-100 text-emerald-800 border-emerald-200';
};

const statusLabel = (status: CommunityUser['status']) => {
    if (status === 'pendiente') return 'Pendiente';
    if (status === 'Ausente') return 'Ausente';
    return 'Disponible';
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
    <AppLayout>
        <div class=" space-y-6 p-6">
            
            <!-- Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">
                        Comunidad
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Directorio interno con estado actual de disponibilidad.
                    </p>
                </div>

                <div class="w-full sm:max-w-sm">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Buscar por nombre, correo, teléfono, rol o área..."
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900"
                    />
                </div>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="py-12 text-center text-slate-500">
                Cargando comunidad...
            </div>

            <!-- Empty -->
            <div
                v-else-if="filteredUsers.length === 0"
                class="rounded-xl border border-slate-200 bg-white p-8 text-center text-slate-500 dark:border-slate-700 dark:bg-slate-900"
            >
                No se encontraron personas con ese criterio de búsqueda.
            </div>

            <!-- Grid -->
            <div
                v-else
                class="grid gap-6 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5"
            >
                <div
                    v-for="user in filteredUsers"
                    :key="user.id"
                    class="card-flip-container h-[420px] w-full"
                >
                    <div class="card-flip-inner">

                        <!-- FRONT -->
                        <article class="card-face card-front border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                            <div class="flex h-full flex-col items-center text-center">

                                <div class="mb-4 mt-2">
                                    <img
                                        v-if="user.photo_url"
                                        :src="user.photo_url"
                                        :alt="`Foto de ${user.name}`"
                                        class="h-32 w-32 rounded-full object-cover ring-4 ring-slate-100 dark:ring-slate-800"
                                    />
                                    <div
                                        v-else
                                        class="flex h-32 w-32 items-center justify-center rounded-full bg-slate-200 text-2xl font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-100"
                                    >
                                        {{ initials(user.name) }}
                                    </div>
                                </div>

                                <h3 class="line-clamp-2 text-base font-semibold text-slate-900 dark:text-slate-100">
                                    {{ user.name }}
                                </h3>

                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    {{ user.role_label }}
                                </p>

                                <span
                                    class="mt-3 inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium"
                                    :class="statusBadgeClass(user.status)"
                                >
                                    {{ statusLabel(user.status) }}
                                </span>

                                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                                    {{ user.area || 'Sin área' }}
                                </p>

                                <div class="mt-auto w-full rounded-lg border border-dashed border-slate-300 p-2 text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                    Pasa el cursor para ver más información
                                </div>
                            </div>
                        </article>

                        <!-- BACK -->
                        <article class="card-face card-back border border-slate-200 bg-slate-900 p-5 text-slate-100 shadow-sm dark:border-slate-700">
                            <h3 class="mb-4 text-base font-semibold">
                                {{ user.name }}
                            </h3>

                            <dl class="space-y-3 text-sm">
                                <div>
                                    <dt class="text-xs uppercase tracking-wide text-slate-400">
                                        Correo
                                    </dt>
                                    <dd class="break-all">
                                        {{ user.email }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-xs uppercase tracking-wide text-slate-400">
                                        Teléfono
                                    </dt>
                                    <dd>
                                        {{ user.phone || 'No registrado' }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-xs uppercase tracking-wide text-slate-400">
                                        Edad
                                    </dt>
                                    <dd>
                                        {{ user.age ? `${user.age} años` : 'No registrada' }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-xs uppercase tracking-wide text-slate-400">
                                        Área
                                    </dt>
                                    <dd>
                                        {{ user.area || 'Sin área asignada' }}
                                    </dd>
                                </div>
                            </dl>
                        </article>

                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.card-flip-container {
    perspective: 1000px;
}

.card-flip-inner {
    position: relative;
    height: 100%;
    width: 100%;
    transform-style: preserve-3d;
    transition: transform 0.6s ease;
}

.card-flip-container:hover .card-flip-inner {
    transform: rotateY(180deg);
}

.card-face {
    position: absolute;
    inset: 0;
    border-radius: 0.75rem;
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
}

.card-back {
    transform: rotateY(180deg);
}
</style>