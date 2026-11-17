<script setup lang="ts">
import { Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import EmptyState from '@/components/app/EmptyState.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import { Input } from '@/components/ui/input';
import { includesNormalized, normalizeForSearch } from '@/lib/search';
import { formatNumber, formatReportDate } from './types';
import type { AbsenceReportRow } from './types';

const props = defineProps<{ rows: AbsenceReportRow[]; showArea: boolean }>();

const query = ref('');
const filtered = computed(() => {
    const q = normalizeForSearch(query.value);
    if (!q) return props.rows;
    return props.rows.filter(
        (r) =>
            includesNormalized(r.empleado, q) ||
            includesNormalized(r.tipo, q) ||
            includesNormalized(r.area, q) ||
            includesNormalized(r.estado, q),
    );
});
</script>

<template>
    <section class="min-w-0 overflow-hidden rounded-xl border bg-card">
        <div
            class="flex flex-col gap-2 border-b px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5"
        >
            <div>
                <h2 class="text-[15px] font-semibold tracking-[-0.01em]">Detalle</h2>
                <p class="text-[13px] text-muted-foreground" aria-live="polite">
                    {{ filtered.length }} de {{ rows.length }} registros
                </p>
            </div>
            <div class="relative w-full sm:w-64">
                <Search
                    class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                    aria-hidden="true"
                />
                <Input
                    v-model="query"
                    type="search"
                    class="pl-8"
                    placeholder="Filtrar resultados"
                    aria-label="Filtrar resultados del reporte"
                />
            </div>
        </div>

        <EmptyState
            v-if="filtered.length === 0"
            compact
            title="Sin coincidencias"
            description="Ningún registro coincide con el texto buscado."
        />
        <div v-else class="max-h-[60vh] overflow-auto">
            <table class="data-table">
                <thead class="sticky top-0 z-10">
                    <tr>
                        <th>Colaborador</th>
                        <th v-if="showArea">Área</th>
                        <th>Tipo</th>
                        <th>Inicio</th>
                        <th>Fin</th>
                        <th class="text-right">Días</th>
                        <th class="text-right">Horas</th>
                        <th>Estado</th>
                        <th>Aprobado por</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in filtered" :key="item.id">
                        <td class="font-medium whitespace-nowrap">{{ item.empleado }}</td>
                        <td v-if="showArea" class="text-muted-foreground">
                            {{ item.area || '—' }}
                        </td>
                        <td>{{ item.tipo }}</td>
                        <td class="whitespace-nowrap text-muted-foreground">
                            {{ formatReportDate(item.inicio) }}
                        </td>
                        <td class="whitespace-nowrap text-muted-foreground">
                            {{ formatReportDate(item.fin) }}
                        </td>
                        <td class="text-right tabular-nums">{{ formatNumber(item.dias) }}</td>
                        <td class="text-right tabular-nums">{{ formatNumber(item.horas) }}</td>
                        <td><StatusBadge :status="item.estado" /></td>
                        <td class="text-muted-foreground">
                            {{ item.aprobado_por || '—' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
