<script setup lang="ts">
import { Check, ChevronDown, Search, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { normalizeForSearch } from '@/lib/search';
import { cn } from '@/lib/utils';

type Option = { value: number | string; label: string; hint?: string };

const props = withDefaults(
    defineProps<{
        options: Option[];
        modelValue: Array<number | string>;
        /** Texto cuando no hay selección (p. ej. "Todos los usuarios") */
        placeholder?: string;
        /** Nombre accesible del filtro (p. ej. "Usuarios") */
        label: string;
        searchPlaceholder?: string;
        emptyText?: string;
        class?: string;
    }>(),
    {
        placeholder: 'Todos',
        searchPlaceholder: 'Buscar…',
        emptyText: 'Sin resultados',
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: Array<number | string>];
}>();

const open = ref(false);
const query = ref('');

watch(open, (isOpen) => {
    if (!isOpen) query.value = '';
});

const selectedSet = computed(() => new Set(props.modelValue.map(String)));

const filtered = computed(() => {
    const term = normalizeForSearch(query.value);
    if (!term) return props.options;
    return props.options.filter((o) =>
        normalizeForSearch(`${o.label} ${o.hint ?? ''}`).includes(term),
    );
});

const summary = computed(() => {
    const count = props.modelValue.length;
    if (count === 0) return props.placeholder;
    if (count === 1) {
        const one = props.options.find(
            (o) => String(o.value) === String(props.modelValue[0]),
        );
        return one?.label ?? '1 seleccionado';
    }
    return `${count} seleccionados`;
});

const toggle = (value: number | string) => {
    const key = String(value);
    const next = selectedSet.value.has(key)
        ? props.modelValue.filter((v) => String(v) !== key)
        : [...props.modelValue, value];
    emit('update:modelValue', next);
};

const clear = () => emit('update:modelValue', []);
</script>

<template>
    <div :class="cn('flex min-w-0 items-center gap-1', props.class)">
        <Popover v-model:open="open">
            <PopoverTrigger as-child>
                <Button
                    variant="outline"
                    class="h-9 w-full min-w-0 justify-between gap-2 font-normal sm:w-56"
                    :aria-label="`${label}: ${summary}`"
                >
                    <span class="flex min-w-0 items-center gap-1.5">
                        <span class="text-muted-foreground">{{ label }}:</span>
                        <span
                            :class="
                                cn(
                                    'truncate',
                                    modelValue.length
                                        ? 'font-medium text-foreground'
                                        : 'text-muted-foreground',
                                )
                            "
                        >
                            {{ summary }}
                        </span>
                    </span>
                    <ChevronDown class="size-4 shrink-0 opacity-60" />
                </Button>
            </PopoverTrigger>
            <PopoverContent class="w-[min(18rem,calc(100vw-2rem))] p-0">
                <div class="flex items-center gap-2 border-b px-3">
                    <Search class="size-4 shrink-0 text-muted-foreground" />
                    <input
                        v-model="query"
                        type="search"
                        :placeholder="searchPlaceholder"
                        :aria-label="`Buscar ${label.toLowerCase()}`"
                        class="h-10 w-full bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                    />
                </div>
                <ul
                    role="listbox"
                    aria-multiselectable="true"
                    :aria-label="label"
                    class="max-h-64 overflow-y-auto p-1"
                >
                    <li
                        v-if="!filtered.length"
                        class="px-2 py-6 text-center text-sm text-muted-foreground"
                    >
                        {{ emptyText }}
                    </li>
                    <li v-for="opt in filtered" :key="String(opt.value)">
                        <button
                            type="button"
                            role="option"
                            :aria-selected="selectedSet.has(String(opt.value))"
                            class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm outline-none hover:bg-accent focus-visible:bg-accent"
                            @click="toggle(opt.value)"
                        >
                            <span
                                :class="
                                    cn(
                                        'flex size-4 shrink-0 items-center justify-center rounded-[4px] border',
                                        selectedSet.has(String(opt.value))
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-input',
                                    )
                                "
                                aria-hidden="true"
                            >
                                <Check
                                    v-if="selectedSet.has(String(opt.value))"
                                    class="size-3"
                                />
                            </span>
                            <span class="min-w-0 flex-1 truncate">{{
                                opt.label
                            }}</span>
                            <span
                                v-if="opt.hint"
                                class="truncate text-xs text-muted-foreground"
                                >{{ opt.hint }}</span
                            >
                        </button>
                    </li>
                </ul>
                <div
                    v-if="modelValue.length"
                    class="flex items-center justify-between border-t px-3 py-2 text-xs text-muted-foreground"
                >
                    <span>{{ modelValue.length }} seleccionados</span>
                    <button
                        type="button"
                        class="font-medium text-primary hover:underline"
                        @click="clear"
                    >
                        Limpiar
                    </button>
                </div>
            </PopoverContent>
        </Popover>
        <Button
            v-if="modelValue.length"
            variant="ghost"
            size="icon-sm"
            :aria-label="`Quitar filtro de ${label.toLowerCase()}`"
            @click="clear"
        >
            <X />
        </Button>
    </div>
</template>
