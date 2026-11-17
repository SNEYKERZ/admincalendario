<script setup lang="ts">
import { CalendarDays, CheckCircle2, Clock, ListChecks, XCircle } from 'lucide-vue-next';
import { computed } from 'vue';
import StatCard from '@/components/app/StatCard.vue';
import { normalizeStatus } from '@/lib/status';
import { formatNumber } from './types';
import type { AbsenceReportRow } from './types';

const props = defineProps<{ rows: AbsenceReportRow[] }>();

const countBy = (status: string) =>
    props.rows.filter((r) => normalizeStatus(r.estado) === status).length;

const totalDays = computed(() =>
    props.rows.reduce((sum, item) => sum + (Number(item.dias) || 0), 0),
);

const CHART_COLORS = [
    'var(--chart-1)',
    'var(--chart-2)',
    'var(--chart-3)',
    'var(--chart-4)',
    'var(--chart-5)',
];

/** Distribución de días por tipo de ausencia (datos reales del reporte). */
const byType = computed(() => {
    const map = new Map<string, { count: number; days: number }>();
    props.rows.forEach((r) => {
        const key = r.tipo || 'Sin tipo';
        const cur = map.get(key) ?? { count: 0, days: 0 };
        cur.count += 1;
        cur.days += Number(r.dias) || 0;
        map.set(key, cur);
    });
    const list = Array.from(map.entries())
        .map(([name, v]) => ({ name, ...v }))
        .sort((a, b) => b.days - a.days);
    const max = Math.max(...list.map((l) => l.days), 1);
    return list.map((l, i) => ({
        ...l,
        pct: (l.days / max) * 100,
        color: CHART_COLORS[i % CHART_COLORS.length],
    }));
});
</script>

<template>
    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,22rem)]">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5 lg:content-start">
            <StatCard label="Registros" :value="rows.length" :icon="ListChecks" />
            <StatCard
                label="Días totales"
                :value="formatNumber(totalDays)"
                :icon="CalendarDays"
                tone="primary"
            />
            <StatCard
                label="Aprobadas"
                :value="countBy('aprobado')"
                :icon="CheckCircle2"
                tone="success"
            />
            <StatCard
                label="Pendientes"
                :value="countBy('pendiente')"
                :icon="Clock"
                tone="warning"
            />
            <StatCard
                label="Rechazadas"
                :value="countBy('rechazado')"
                :icon="XCircle"
                tone="destructive"
            />
        </div>

        <section class="rounded-xl border bg-card p-4">
            <h3 class="text-[13px] font-medium text-muted-foreground">
                Días por tipo de ausencia
            </h3>
            <ul class="mt-3 space-y-2.5">
                <li v-for="t in byType" :key="t.name" class="space-y-1">
                    <div class="flex items-baseline justify-between gap-2 text-sm">
                        <span class="truncate">{{ t.name }}</span>
                        <span class="shrink-0 text-xs text-muted-foreground tabular-nums">
                            {{ formatNumber(t.days) }} días · {{ t.count }}
                        </span>
                    </div>
                    <div
                        class="h-1.5 overflow-hidden rounded-full bg-muted"
                        role="img"
                        :aria-label="`${t.name}: ${formatNumber(t.days)} días`"
                    >
                        <div
                            class="h-full rounded-full"
                            :style="{ width: `${t.pct}%`, backgroundColor: t.color }"
                        />
                    </div>
                </li>
            </ul>
        </section>
    </div>
</template>
