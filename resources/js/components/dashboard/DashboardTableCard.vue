<script setup lang="ts">
import { useDebounceFn } from '@vueuse/core';
import { Search, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import SectionCard from '@/components/app/SectionCard.vue';
import TablePagination from '@/components/app/TablePagination.vue';
import { Skeleton } from '@/components/ui/skeleton';
import type { PaginationMeta } from './types';

/**
 * Tarjeta de tabla del dashboard: título, búsqueda en vivo (con debounce),
 * tamaño de página y paginación. El contenido de la tabla va en el slot.
 */
const props = withDefaults(
    defineProps<{
        title: string;
        description?: string;
        search: string;
        perPage: number;
        meta: PaginationMeta;
        loading?: boolean;
        /** true sólo en la primera carga, para mostrar skeleton */
        initialLoading?: boolean;
        searchPlaceholder?: string;
        searchable?: boolean;
        paginated?: boolean;
        perPageOptions?: number[];
        idPrefix: string;
    }>(),
    {
        searchPlaceholder: 'Buscar…',
        searchable: true,
        paginated: true,
        perPageOptions: () => [5, 10],
    },
);

const emit = defineEmits<{
    search: [value: string];
    perPage: [value: number];
    page: [value: number];
}>();

const query = ref(props.search);

watch(
    () => props.search,
    (value) => {
        if (value !== query.value) query.value = value;
    },
);

const emitSearch = useDebounceFn((value: string) => {
    emit('search', value.trim());
}, 350);

const onInput = (event: Event) => {
    query.value = (event.target as HTMLInputElement).value;
    emitSearch(query.value);
};

const clear = () => {
    query.value = '';
    emit('search', '');
};
</script>

<template>
    <SectionCard flush>
        <template #header>
            <div class="min-w-0">
                <h2 class="text-[15px] font-semibold tracking-[-0.01em]">
                    {{ title }}
                </h2>
                <p
                    v-if="description"
                    class="mt-0.5 text-[13px] text-muted-foreground"
                >
                    {{ description }}
                </p>
            </div>
        </template>
        <template v-if="searchable || paginated" #actions>
            <div v-if="searchable" class="relative w-full sm:w-56">
                <Search
                    class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                    aria-hidden="true"
                />
                <label :for="`${idPrefix}-search`" class="sr-only">
                    {{ searchPlaceholder }}
                </label>
                <input
                    :id="`${idPrefix}-search`"
                    :value="query"
                    type="search"
                    class="input h-8 pr-8 pl-8"
                    :placeholder="searchPlaceholder"
                    autocomplete="off"
                    @input="onInput"
                    @keydown.enter.prevent="emit('search', query.trim())"
                />
                <button
                    v-if="query"
                    type="button"
                    class="absolute top-1/2 right-2 -translate-y-1/2 rounded text-muted-foreground hover:text-foreground"
                    aria-label="Limpiar búsqueda"
                    @click="clear"
                >
                    <X class="size-4" />
                </button>
            </div>
            <div v-if="paginated" class="flex items-center gap-1.5">
                <label
                    :for="`${idPrefix}-per-page`"
                    class="text-xs whitespace-nowrap text-muted-foreground"
                >
                    Filas
                </label>
                <select
                    :id="`${idPrefix}-per-page`"
                    :value="perPage"
                    class="input h-8 w-[4.5rem]"
                    @change="
                        emit(
                            'perPage',
                            Number(($event.target as HTMLSelectElement).value),
                        )
                    "
                >
                    <option
                        v-for="option in perPageOptions"
                        :key="option"
                        :value="option"
                    >
                        {{ option }}
                    </option>
                </select>
            </div>
        </template>

        <slot name="before" />

        <div v-if="initialLoading" class="space-y-2 p-4">
            <Skeleton v-for="n in 4" :key="n" class="h-8 w-full" />
        </div>
        <div
            v-else
            class="overflow-x-auto transition-opacity"
            :class="loading ? 'pointer-events-none opacity-60' : ''"
            :aria-busy="loading"
        >
            <slot />
        </div>

        <template v-if="paginated && !initialLoading && meta.total > 0" #footer>
            <TablePagination
                class="w-full"
                :page="meta.current_page"
                :last-page="meta.last_page"
                :total="meta.total"
                @change="emit('page', $event)"
            />
        </template>
    </SectionCard>
</template>
