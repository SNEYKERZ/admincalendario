<script setup lang="ts">
import { BarChart3, Building2, User } from 'lucide-vue-next';
import FormField from '@/components/app/FormField.vue';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import type { FilterOption, ReportScope } from './types';

defineProps<{
    years: number[];
    users: FilterOption[];
    areas: FilterOption[];
    loadingOptions: boolean;
    generating: boolean;
    canGenerate: boolean;
    error: string | null;
}>();

const scope = defineModel<ReportScope>('scope', { required: true });
const year = defineModel<number>('year', { required: true });
const userId = defineModel<string>('userId', { required: true });
const areaId = defineModel<string>('areaId', { required: true });

const emit = defineEmits<{ generate: [] }>();

const scopes = [
    { value: 'personal', label: 'Por persona', icon: User },
    { value: 'area', label: 'Por área', icon: Building2 },
] as const;
</script>

<template>
    <section class="rounded-xl border bg-card">
        <form
            class="grid gap-4 p-4 sm:p-5 md:grid-cols-[auto_8rem_minmax(0,1fr)_auto] md:items-start"
            @submit.prevent="emit('generate')"
        >
            <FormField label="Tipo de reporte">
                <div
                    role="radiogroup"
                    aria-label="Tipo de reporte"
                    class="grid h-9 grid-cols-2 gap-1 rounded-lg bg-muted p-1"
                >
                    <button
                        v-for="opt in scopes"
                        :key="opt.value"
                        type="button"
                        role="radio"
                        :aria-checked="scope === opt.value"
                        :class="
                            cn(
                                'flex items-center justify-center gap-1.5 rounded-md px-3 text-sm font-medium whitespace-nowrap transition-colors',
                                scope === opt.value
                                    ? 'bg-card text-foreground shadow-xs'
                                    : 'text-muted-foreground hover:text-foreground',
                            )
                        "
                        @click="scope = opt.value"
                    >
                        <component :is="opt.icon" class="size-4" />
                        {{ opt.label }}
                    </button>
                </div>
            </FormField>

            <FormField label="Año" for="report-year" required>
                <select id="report-year" v-model.number="year" class="input">
                    <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                </select>
            </FormField>

            <FormField
                v-if="scope === 'personal'"
                label="Persona"
                for="report-user"
                required
                :error="error"
            >
                <Skeleton v-if="loadingOptions" class="h-9 w-full" />
                <select
                    v-else
                    id="report-user"
                    v-model="userId"
                    class="input"
                    :aria-invalid="!!error"
                >
                    <option value="">Selecciona una persona…</option>
                    <option v-for="u in users" :key="u.id" :value="String(u.id)">
                        {{ u.name }}
                    </option>
                </select>
            </FormField>

            <FormField
                v-else
                label="Área"
                for="report-area"
                required
                :error="error"
            >
                <Skeleton v-if="loadingOptions" class="h-9 w-full" />
                <select
                    v-else
                    id="report-area"
                    v-model="areaId"
                    class="input"
                    :aria-invalid="!!error"
                >
                    <option value="">Selecciona un área…</option>
                    <option v-for="a in areas" :key="a.id" :value="String(a.id)">
                        {{ a.name }}
                    </option>
                </select>
            </FormField>

            <div class="md:pt-[22px]">
                <Button
                    type="submit"
                    class="w-full md:w-auto"
                    :disabled="generating || !canGenerate"
                >
                    <Spinner v-if="generating" />
                    <BarChart3 v-else />
                    {{ generating ? 'Generando…' : 'Generar reporte' }}
                </Button>
            </div>
        </form>
    </section>
</template>
