<script setup lang="ts">
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import FullCalendar from '@fullcalendar/vue3';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { Plus } from 'lucide-vue-next';
import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AbsenceModal from '@/components/AbsenceModal.vue';
import MultiSelectFilter from '@/components/app/MultiSelectFilter.vue';
import PageContainer from '@/components/app/PageContainer.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { useNotification } from '@/composables/useNotification';
import AppLayout from '@/layouts/AppLayout.vue';
import { getStatusLabel, getStatusTone  } from '@/lib/status';
import type {StatusTone} from '@/lib/status';

type CalendarUser = {
    id: number;
    name: string;
    identification: string;
    email: string;
};

const calendarRef = ref<any>(null);
const showModal = ref(false);
const modalMode = ref('create');
const selectedRange = ref<any>(null);
const selectedAbsence = ref<any>(null);
const selectedUsers = ref<Array<number | string>>([]);
const selectedAreas = ref<Array<number | string>>([]);
const users = ref<CalendarUser[]>([]);
const areas = ref<{ id: number; name: string }[]>([]);
const isAdmin = ref(false);
const notify = useNotification();
const selectedCountry = ref('CO');
const holidays = ref<{ date: string; title: string }[]>([]);
const countries = ref<{ code: string; name: string }[]>([]);
const currentUserId = ref<number | null>(null);
const ready = ref(false);
const isMobile = ref(false);

const updateViewportFlags = () => {
    isMobile.value = window.innerWidth < 640;
};

const userOptions = computed(() =>
    users.value.map((u) => ({
        value: u.id,
        label: u.name,
        hint: u.identification || undefined,
    })),
);

const areaOptions = computed(() =>
    areas.value.map((a) => ({ value: a.id, label: a.name })),
);

onMounted(async () => {
    updateViewportFlags();
    window.addEventListener('resize', updateViewportFlags);

    try {
        const userRes = await axios.get('/me');
        const user = userRes.data;
        currentUserId.value = user?.id || null;
        isAdmin.value =
            user?.role === 'admin' ||
            user?.role === 'superadmin' ||
            !!user?.is_admin;

        // Usuarios disponibles para filtro/asignación.
        const usersRes = await axios.get('/users-list');
        users.value = (usersRes.data ?? []).map((u: any) => ({
            id: u.id,
            name: u.name,
            identification: u.identification || '',
            email: u.email || '',
        }));

        // Áreas disponibles
        try {
            const areasRes = await axios.get('/api/areas');
            areas.value = (areasRes.data?.areas ?? []).map((a: any) => ({
                id: a.id,
                name: a.name,
            }));
        } catch (e) {
            console.error('Error loading areas:', e);
        }

        await loadCountries();
        await loadHolidays(new Date().getFullYear());
    } catch (e) {
        console.error('Error loading data:', e);
        notify.error('No se pudieron cargar los datos del calendario.');
    } finally {
        ready.value = true;
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', updateViewportFlags);
});

watch(selectedCountry, async (newCountry) => {
    if (newCountry) {
        await loadHolidays(new Date().getFullYear());
        refreshCalendar();
    }
});

watch([selectedUsers, selectedAreas], () => refreshCalendar());

const loadHolidays = async (year: number) => {
    try {
        const res = await axios.get('/holidays', {
            params: { year, country: selectedCountry.value },
        });
        holidays.value = res.data;
    } catch (e) {
        console.error('Error loading holidays:', e);
    }
};

const loadCountries = async () => {
    try {
        const res = await axios.get('/holidays/countries');
        countries.value = Object.entries(res.data).map(([code, name]) => ({
            code,
            name: name as string,
        }));
    } catch (e) {
        console.error('Error loading countries:', e);
        countries.value = [{ code: 'CO', name: 'Colombia' }];
    }
};

/** Colores de evento a partir de los tokens semánticos (legibles en claro/oscuro). */
const toneColors = (tone: StatusTone) => {
    const base = tone === 'neutral' ? 'var(--muted-foreground)' : `var(--${tone})`;
    return {
        backgroundColor:
            tone === 'neutral' ? 'var(--muted)' : `var(--${tone}-soft)`,
        borderColor: `color-mix(in oklch, ${base} 35%, transparent)`,
        textColor: base,
    };
};

const fetchEvents = async (
    info: any,
    successCallback: any,
    failureCallback: any,
) => {
    try {
        const params: any = {
            start: info.startStr,
            end: info.endStr,
        };

        if (selectedUsers.value.length > 0) {
            params.user_ids = selectedUsers.value.join(',');
        } else if (!isAdmin.value && currentUserId.value) {
            // Usuario normal: siempre ver sus propias ausencias
            params.user_ids = currentUserId.value;
        }

        if (selectedAreas.value.length > 0) {
            params.area_ids = selectedAreas.value.join(',');
        }

        const res = await axios.get('/absences', { params });

        const absenceEvents = (res.data ?? []).map((item: any) => ({
            id: item.id,
            title: `${item.user?.name ?? ''} · ${item.type?.name ?? ''}`,
            start: item.start_datetime,
            end: item.end_datetime,
            ...toneColors(getStatusTone(item.status)),
            editable: item.status === 'pendiente',
            extendedProps: item,
        }));

        const holidayEvents = holidays.value.map((h: any) => ({
            start: h.date,
            allDay: true,
            display: 'background',
            backgroundColor: 'var(--info-soft)',
            classNames: ['holiday-event'],
            title: h.title,
            extendedProps: { isHoliday: true },
        }));

        successCallback([...absenceEvents, ...holidayEvents]);
    } catch (e) {
        console.error(e);
        notify.error('No se pudieron cargar las ausencias.');
        failureCallback(e);
    }
};

const handleDateSelect = (info: any) => {
    modalMode.value = 'create';
    selectedAbsence.value = null;
    selectedRange.value = info;
    showModal.value = true;
};

const openCreate = () => {
    modalMode.value = 'create';
    selectedAbsence.value = null;
    selectedRange.value = null;
    showModal.value = true;
};

const handleEventClick = async (info: any) => {
    if (info.event.extendedProps?.isHoliday) return;
    try {
        const res = await axios.get(`/absences/${info.event.id}`);
        modalMode.value = 'view';
        selectedAbsence.value = res.data;
        showModal.value = true;
    } catch {
        notify.error('No se pudo cargar la ausencia.');
    }
};

const refreshCalendar = () => {
    calendarRef.value?.getApi()?.refetchEvents();
};

/** Construye el contenido del tooltip con nodos de texto (sin HTML interpolado). */
const buildTooltip = (rows: Array<[string, string] | string>) => {
    const root = document.createElement('div');
    root.className = 'space-y-0.5 px-1 py-0.5';
    rows.forEach((row, index) => {
        const line = document.createElement('div');
        if (typeof row === 'string') {
            line.textContent = row;
            if (index === 0) line.style.fontWeight = '600';
        } else {
            const label = document.createElement('span');
            label.style.opacity = '0.7';
            label.textContent = `${row[0]}: `;
            line.appendChild(label);
            line.appendChild(document.createTextNode(row[1]));
        }
        root.appendChild(line);
    });
    return root;
};

const formatDate = (value: string) =>
    new Date(value).toLocaleDateString('es-CO', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });

const handleEventDidMount = (info: any) => {
    try {
        const data = info.event.extendedProps;

        if (data?.isHoliday) {
            tippy(info.el, {
                content: buildTooltip([`Festivo: ${info.event.title}`]),
                theme: 'app',
            });
            return;
        }

        if (!data?.user) return;

        tippy(info.el, {
            content: buildTooltip([
                data.user.name ?? '',
                ['Tipo', data.type?.name ?? '—'],
                ['Estado', getStatusLabel(data.status)],
                ['Inicio', formatDate(data.start_datetime)],
                ['Fin', formatDate(data.end_datetime)],
            ]),
            theme: 'app',
        });
    } catch (e) {
        console.error('Tooltip error', e);
    }
};

const handleEventDrop = async (info: any) => {
    try {
        await axios.put(`/absences/${info.event.id}`, {
            start_datetime: info.event.start,
            end_datetime: info.event.end,
        });
        notify.success('Fechas de la ausencia actualizadas.');
    } catch (e: any) {
        info.revert();
        notify.error(
            e?.response?.data?.message || 'No se pudo mover la ausencia.',
        );
    }
};

const handleEventResize = async (info: any) => {
    try {
        await axios.put(`/absences/${info.event.id}`, {
            start_datetime: info.event.start,
            end_datetime: info.event.end,
        });
        notify.success('Duración actualizada.');
    } catch (e: any) {
        info.revert();
        notify.error(
            e?.response?.data?.message || 'No se pudo cambiar la duración.',
        );
    }
};

// Sólo las ausencias pendientes se pueden mover o redimensionar
// (el backend responde 422/403 al editar ausencias ya resueltas).
const canEditEvent = (_dropInfo: any, draggedEvent: any) =>
    draggedEvent?.extendedProps?.status === 'pendiente';

const legend = [
    { label: 'Pendiente', tone: 'warning' },
    { label: 'Aprobado', tone: 'success' },
    { label: 'Rechazado', tone: 'destructive' },
    { label: 'Festivo', tone: 'info' },
] as const;

const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: isMobile.value ? 'dayGridWeek' : 'dayGridMonth',
    selectable: true,
    editable: true,
    events: fetchEvents,
    select: handleDateSelect,
    eventClick: handleEventClick,
    eventDrop: handleEventDrop,
    eventResize: handleEventResize,
    eventDidMount: handleEventDidMount,
    eventAllow: canEditEvent,
    eventDisplay: 'block',
    height: 'auto',
    dayMaxEvents: isMobile.value ? 2 : 3,
    headerToolbar: isMobile.value
        ? {
              left: 'prev,next',
              center: 'title',
              right: 'today',
          }
        : {
              left: 'prev,next today',
              center: 'title',
              right: 'dayGridMonth,timeGridWeek,timeGridDay',
          },
    footerToolbar: isMobile.value
        ? { center: 'dayGridMonth,dayGridWeek,timeGridDay' }
        : undefined,
    locale: 'es',
    firstDay: 1,
    buttonText: {
        today: 'Hoy',
        month: 'Mes',
        week: 'Semana',
        day: 'Día',
        list: 'Lista',
    },
    moreLinkText: (n: number) => `+${n} más`,
    allDayText: 'Todo el día',
    noEventsText: 'No hay ausencias en este periodo',
}));
</script>

<template>
    <Head title="Calendario" />
    <AppLayout>
        <PageContainer>
            <PageHeader
                title="Calendario"
                description="Ausencias y novedades del equipo. Arrastra sobre los días para registrar una nueva."
            >
                <template #actions>
                    <Button size="sm" @click="openCreate">
                        <Plus /> Nueva ausencia
                    </Button>
                </template>
            </PageHeader>

            <!-- Filtros + leyenda -->
            <div
                class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
            >
                <div
                    class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center"
                    role="group"
                    aria-label="Filtros del calendario"
                >
                    <template v-if="ready">
                        <MultiSelectFilter
                            v-model="selectedUsers"
                            label="Personas"
                            placeholder="Todas"
                            search-placeholder="Buscar persona…"
                            empty-text="No se encontraron personas"
                            :options="userOptions"
                        />
                        <MultiSelectFilter
                            v-if="areaOptions.length"
                            v-model="selectedAreas"
                            label="Áreas"
                            placeholder="Todas"
                            search-placeholder="Buscar área…"
                            empty-text="No se encontraron áreas"
                            :options="areaOptions"
                        />
                    </template>
                    <template v-else>
                        <Skeleton class="h-9 w-full sm:w-56" />
                        <Skeleton class="h-9 w-full sm:w-56" />
                    </template>
                    <div class="flex items-center gap-2">
                        <label
                            for="calendar-holidays-country"
                            class="text-[13px] whitespace-nowrap text-muted-foreground"
                        >
                            Festivos de
                        </label>
                        <select
                            id="calendar-holidays-country"
                            v-model="selectedCountry"
                            class="input h-9 sm:w-44"
                        >
                            <option
                                v-for="country in countries"
                                :key="country.code"
                                :value="country.code"
                            >
                                {{ country.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <ul
                    class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-[13px] text-muted-foreground"
                    aria-label="Leyenda de colores"
                >
                    <li
                        v-for="item in legend"
                        :key="item.label"
                        class="flex items-center gap-1.5"
                    >
                        <span
                            class="size-2.5 rounded-[3px] border"
                            :style="{
                                backgroundColor: `var(--${item.tone}-soft)`,
                                borderColor: `var(--${item.tone})`,
                            }"
                            aria-hidden="true"
                        />
                        {{ item.label }}
                    </li>
                </ul>
            </div>

            <div class="calendar-shell rounded-xl border bg-card p-2 sm:p-4">
                <FullCalendar ref="calendarRef" :options="calendarOptions" />
            </div>
        </PageContainer>

        <AbsenceModal
            :show="showModal"
            :mode="modalMode"
            :absence="selectedAbsence"
            :selected-range="selectedRange"
            :users="users"
            :is-admin="isAdmin"
            @close="showModal = false"
            @saved="refreshCalendar"
        />
    </AppLayout>
</template>

<style>
.calendar-shell .fc .holiday-event .fc-event-title {
    color: var(--info);
}

.calendar-shell .fc-h-event,
.calendar-shell .fc-v-event {
    border-width: 1px;
}

.calendar-shell .fc-h-event .fc-event-main,
.calendar-shell .fc-v-event .fc-event-main {
    color: inherit;
}
</style>
