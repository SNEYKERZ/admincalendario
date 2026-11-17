<script setup lang="ts">
import { FilterX } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

defineProps<{
    users: { id: number; name: string }[];
    areas: { id: number; name: string }[];
    disabled?: boolean;
}>();

const user = defineModel<number | null>('user', { default: null });
const area = defineModel<number | null>('area', { default: null });

const emit = defineEmits<{ change: [] }>();

const onUser = (event: Event) => {
    const value = (event.target as HTMLSelectElement).value;
    user.value = value ? Number(value) : null;
    emit('change');
};

const onArea = (event: Event) => {
    const value = (event.target as HTMLSelectElement).value;
    area.value = value ? Number(value) : null;
    emit('change');
};

const reset = () => {
    user.value = null;
    area.value = null;
    emit('change');
};
</script>

<template>
    <div
        class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center"
        role="group"
        aria-label="Filtros del panel"
    >
        <div class="flex items-center gap-2">
            <label
                for="dashboard-filter-user"
                class="w-20 shrink-0 text-[13px] text-muted-foreground sm:w-auto"
            >
                Colaborador
            </label>
            <select
                id="dashboard-filter-user"
                :value="user ?? ''"
                class="input h-8 sm:w-56"
                :disabled="disabled"
                @change="onUser"
            >
                <option value="">Todos</option>
                <option v-for="u in users" :key="u.id" :value="u.id">
                    {{ u.name }}
                </option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            <label
                for="dashboard-filter-area"
                class="w-20 shrink-0 text-[13px] text-muted-foreground sm:w-auto"
            >
                Área
            </label>
            <select
                id="dashboard-filter-area"
                :value="area ?? ''"
                class="input h-8 sm:w-48"
                :disabled="disabled"
                @change="onArea"
            >
                <option value="">Todas</option>
                <option v-for="a in areas" :key="a.id" :value="a.id">
                    {{ a.name }}
                </option>
            </select>
        </div>
        <Button
            v-if="user !== null || area !== null"
            variant="ghost"
            size="sm"
            class="self-start sm:self-auto"
            @click="reset"
        >
            <FilterX /> Quitar filtros
        </Button>
    </div>
</template>
