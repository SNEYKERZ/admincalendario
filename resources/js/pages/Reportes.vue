<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { BarChart3, Download, SearchX } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import EmptyState from '@/components/app/EmptyState.vue';
import PageContainer from '@/components/app/PageContainer.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import ReportFilters from '@/components/reports/ReportFilters.vue';
import ReportResultsTable from '@/components/reports/ReportResultsTable.vue';
import ReportSummary from '@/components/reports/ReportSummary.vue';
import type { AbsenceReportRow, ReportScope } from '@/components/reports/types';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { useNotification } from '@/composables/useNotification';
import { useReportFilters } from '@/composables/useReportFilters';
import { useReportValidation } from '@/composables/useReportValidation';
import AppLayout from '@/layouts/AppLayout.vue';

const notify = useNotification();

const currentYear = new Date().getFullYear();
const reportType = ref<ReportScope>('personal');
const selectedYear = ref<number>(currentYear);
const selectedUser = ref('');
const selectedArea = ref('');
const loading = ref(false);
const exporting = ref(false);
const generated = ref(false);
const showValidation = ref(false);
const availableYears = Array.from({ length: 10 }, (_, i) => currentYear - i);

const startDate = computed(() => `${selectedYear.value}-01-01`);
const endDate = computed(() => `${selectedYear.value}-12-31`);

const { users, areas, loading: filtersLoading, load: loadFilters } = useReportFilters();

const validation = useReportValidation({
    reportType,
    selectedUser,
    selectedArea,
    selectedYear,
});

const absencesData = ref<AbsenceReportRow[]>([]);

const fieldError = computed(() =>
    showValidation.value ? validation.getFirstError.value : null,
);

const scopeLabel = computed(() => {
    if (reportType.value === 'personal') {
        const u = users.value.find((x) => String(x.id) === selectedUser.value);
        return u ? u.name : null;
    }
    const a = areas.value.find((x) => String(x.id) === selectedArea.value);
    return a ? `Área ${a.name}` : null;
});

const scopeParams = () => {
    const params: Record<string, string> = {};
    if (reportType.value === 'personal' && selectedUser.value) {
        params.user_id = selectedUser.value;
    } else if (reportType.value === 'area' && selectedArea.value) {
        params.area_id = selectedArea.value;
    }
    return params;
};

const loadReport = async () => {
    if (!validation.isValid.value) {
        showValidation.value = true;
        return;
    }
    showValidation.value = false;

    loading.value = true;
    try {
        const params = new URLSearchParams({
            type: 'absences',
            start: startDate.value,
            end: endDate.value,
            ...scopeParams(),
        });
        const res = await axios.get(`/reports?${params}`);
        if (!Array.isArray(res.data?.data)) {
            throw new Error('Estructura de datos inválida en respuesta');
        }
        absencesData.value = res.data.data;
        generated.value = true;
    } catch (e) {
        console.error('Error cargando reporte:', e);
        notify.error('No se pudo generar el reporte. Inténtalo de nuevo.');
        absencesData.value = [];
    } finally {
        loading.value = false;
    }
};

const exportReport = async () => {
    if (!validation.isValid.value) {
        showValidation.value = true;
        return;
    }
    exporting.value = true;
    try {
        // El backend sólo reconoce type=absences|vacations|summary; el tipo
        // "personal/area" se expresa con user_id / area_id.
        const params = new URLSearchParams({
            type: 'absences',
            start: startDate.value,
            end: endDate.value,
            format: 'csv',
            ...scopeParams(),
        });

        const response = await axios.get(`/reports/export?${params}`, {
            responseType: 'blob',
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        const suffix =
            reportType.value === 'area' && selectedArea.value
                ? `-area-${selectedArea.value}`
                : selectedUser.value
                  ? `-persona-${selectedUser.value}`
                  : '';
        link.setAttribute(
            'download',
            `reporte-ausencias-${startDate.value}-${endDate.value}${suffix}.csv`,
        );
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);

        notify.success('Reporte exportado');
    } catch (e) {
        console.error('Error exportando reporte:', e);
        notify.error('No se pudo exportar el reporte');
    } finally {
        exporting.value = false;
    }
};

onMounted(loadFilters);

// Cambiar el año o el alcance regenera el reporte si ya hay una selección válida.
watch(selectedYear, () => {
    if (generated.value && validation.isValid.value) loadReport();
});

watch(reportType, () => {
    showValidation.value = false;
    absencesData.value = [];
    generated.value = false;
});

watch([selectedUser, selectedArea], () => {
    if (validation.isValid.value) loadReport();
});
</script>

<template>
    <Head title="Reportes" />
    <AppLayout>
        <PageContainer>
            <PageHeader
                title="Reportes"
                description="Ausencias y novedades por persona o por área, con exportación a CSV."
            >
                <template #actions>
                    <Button
                        variant="outline"
                        :disabled="exporting || !validation.isValid.value"
                        :title="
                            validation.isValid.value
                                ? 'Descargar el reporte en CSV'
                                : 'Selecciona una persona o un área para exportar'
                        "
                        @click="exportReport"
                    >
                        <Spinner v-if="exporting" />
                        <Download v-else />
                        {{ exporting ? 'Exportando…' : 'Exportar CSV' }}
                    </Button>
                </template>
            </PageHeader>

            <ReportFilters
                v-model:scope="reportType"
                v-model:year="selectedYear"
                v-model:user-id="selectedUser"
                v-model:area-id="selectedArea"
                :years="availableYears"
                :users="users"
                :areas="areas"
                :loading-options="filtersLoading"
                :generating="loading"
                :can-generate="validation.isValid.value"
                :error="fieldError"
                @generate="loadReport"
            />

            <!-- Cargando -->
            <div v-if="loading" class="space-y-4" aria-busy="true" aria-live="polite">
                <span class="sr-only">Generando reporte…</span>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                    <Skeleton v-for="i in 5" :key="i" class="h-[92px] rounded-xl" />
                </div>
                <Skeleton class="h-72 rounded-xl" />
            </div>

            <!-- Resultados -->
            <template v-else-if="absencesData.length > 0">
                <p class="-mb-2 text-[13px] text-muted-foreground">
                    Resultados de
                    <strong class="font-medium text-foreground">{{ scopeLabel }}</strong>
                    · año {{ selectedYear }}
                </p>
                <ReportSummary :rows="absencesData" />
                <ReportResultsTable
                    :rows="absencesData"
                    :show-area="reportType === 'area'"
                />
            </template>

            <!-- Generado sin datos -->
            <section v-else-if="generated" class="rounded-xl border bg-card">
                <EmptyState
                    :icon="SearchX"
                    title="Sin ausencias en este periodo"
                    :description="`No hay registros para ${scopeLabel ?? 'esta selección'} en ${selectedYear}. Prueba con otro año.`"
                />
            </section>

            <!-- Estado inicial -->
            <section v-else class="rounded-xl border bg-card">
                <EmptyState
                    :icon="BarChart3"
                    title="Configura tu reporte"
                    description="Elige si quieres ver una persona o un área, selecciónala y el reporte se generará automáticamente."
                />
            </section>
        </PageContainer>
    </AppLayout>
</template>
