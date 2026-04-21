<script setup lang="ts">
import { computed, ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import axios from 'axios';
import AbsenceModal from '@/components/AbsenceModal.vue';
import { useToast } from 'vue-toastification';
import tippy from 'tippy.js';
import $ from 'jquery';
import { normalizeForSearch } from '@/lib/search';
import 'tippy.js/dist/tippy.css';
import 'select2/dist/css/select2.min.css';

const calendarRef = ref<any>(null);
const showModal = ref(false);
const modalMode = ref('create');
const selectedRange = ref(null);
const selectedAbsence = ref(null);
const selectedUsers = ref<number[]>([]);
const usersSelectRef = ref<HTMLSelectElement | null>(null);
const users = ref<
    { id: number; name: string; identification: string; email: string }[]
>([]);
const isAdmin = ref(false);
const toast = useToast();
const selectedCountry = ref('CO');
const holidays = ref<{ date: string; title: string }[]>([]);
const countries = ref<{ code: string; name: string }[]>([]);

let usersSelect2: any = null;
let select2Loaded = false;
const isMobile = ref(false);

const updateViewportFlags = () => {
    isMobile.value = window.innerWidth < 640;
};

onMounted(async () => {
    updateViewportFlags();
    window.addEventListener('resize', updateViewportFlags);

    try {
        const userRes = await axios.get('/me');
        const user = userRes.data;
        isAdmin.value =
            user?.role === 'admin' ||
            user?.role === 'superadmin' ||
            user?.is_admin;

        // Cargar todos los usuarios disponibles para selección/asignación.
        const usersRes = await axios.get('/users-list');
        users.value = usersRes.data.map((u: any) => ({
            id: u.id,
            name: u.name,
            identification: u.identification || '',
            email: u.email || '',
        }));

        // Load countries and holidays
        await loadCountries();
        await loadHolidays(new Date().getFullYear());
        await nextTick();
        await initUsersSelect2();
    } catch (e) {
        console.error('Error loading data:', e);
    }
});

onBeforeUnmount(() => {
    destroyUsersSelect2();
    window.removeEventListener('resize', updateViewportFlags);
});

// Reload holidays when country changes
watch(selectedCountry, async (newCountry) => {
    if (newCountry) {
        await loadHolidays(new Date().getFullYear());
        refreshCalendar();
    }
});

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
        // Fallback
        countries.value = [{ code: 'CO', name: 'Colombia' }];
    }
};

const getColor = (status: string) => {
    switch (status) {
        case 'pendiente':
            return '#f59e0b';
        case 'aprobado':
            return '#22c55e';
        case 'rechazado':
            return '#ef4444';
        default:
            return '#9ca3af';
    }
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

        // Filtrar por usuarios seleccionados.
        if (selectedUsers.value.length > 0) {
            params.user_ids = selectedUsers.value.join(',');
        }

        const res = await axios.get('/absences', { params });

        const absences = res.data;

        const absenceEvents = absences.map((item: any) => ({
            id: item.id,
            title: `${item.user?.name ?? ''} - ${item.type?.name ?? ''}`,
            start: item.start_datetime,
            end: item.end_datetime,
            color: getColor(item.status),
            extendedProps: item,
        }));

        // Add holidays as background events
        const holidayEvents = holidays.value.map((h: any) => ({
            start: h.date,
            allDay: true,
            display: 'background',
            backgroundColor: '#fde68a',
            title: h.title,
            extendedProps: { isHoliday: true },
        }));

        successCallback([...absenceEvents, ...holidayEvents]);
    } catch (e) {
        console.error(e);
        failureCallback(e);
    }
};

const handleDateSelect = (info: any) => {
    modalMode.value = 'create';
    selectedRange.value = info;
    showModal.value = true;
};

const handleEventClick = async (info: any) => {
    try {
        const res = await axios.get(`/absences/${info.event.id}`);
        modalMode.value = 'view';
        selectedAbsence.value = res.data;
        showModal.value = true;
    } catch (e) {
        toast.error('Error al cargar ausencia');
    }
};

const refreshCalendar = () => {
    calendarRef.value?.getApi()?.refetchEvents();
};

const handleUserChange = (userIds: number[]) => {
    selectedUsers.value = userIds;
    refreshCalendar();
};

const handleUsersSelect2Change = () => {
    if (!usersSelect2) return;

    const rawValue = usersSelect2.val();
    const ids = Array.isArray(rawValue)
        ? rawValue
              .map((value: string | number) => Number(value))
              .filter((value: number) => Number.isInteger(value))
        : [];

    handleUserChange(ids);
};

const destroyUsersSelect2 = () => {
    if (!usersSelect2) return;
    usersSelect2.off('.calendarUsers');
    usersSelect2.select2('destroy');
    usersSelect2 = null;
};

const ensureSelect2Loaded = async () => {
    if (select2Loaded && typeof ($ as any).fn?.select2 === 'function') {
        return true;
    }

    // Select2 necesita jQuery en el objeto global.
    (window as any).$ = $;
    (window as any).jQuery = $;

    if (typeof ($ as any).fn?.select2 !== 'function') {
        const select2Module: any = await import('select2');
        const select2Initializer = select2Module.default ?? select2Module;

        if (typeof select2Initializer === 'function') {
            select2Initializer(window, $);
        }
    }

    if (typeof ($ as any).fn?.select2 !== 'function') {
        const select2FullModule: any = await import('select2/dist/js/select2.full.js');
        const select2FullInitializer =
            select2FullModule.default ?? select2FullModule;

        if (typeof select2FullInitializer === 'function') {
            select2FullInitializer(window, $);
        }
    }

    select2Loaded = typeof ($ as any).fn?.select2 === 'function';
    return select2Loaded;
};

const initUsersSelect2 = async () => {
    if (!usersSelectRef.value) return;

    const pluginReady = await ensureSelect2Loaded();
    if (!pluginReady) {
        console.error('Select2 no pudo inicializarse');
        return;
    }

    destroyUsersSelect2();

    usersSelect2 = $(usersSelectRef.value);
    usersSelect2.select2({
        width: '100%',
        placeholder: 'Todos los usuarios',
        allowClear: true,
        closeOnSelect: false,
        minimumResultsForSearch: 0,
        matcher: (params: any, data: any) => {
            const term = normalizeForSearch(params?.term);
            if (!term) return data;

            const text = normalizeForSearch(data?.text);
            return text.includes(term) ? data : null;
        },
        language: {
            noResults: () => 'No se encontraron usuarios',
            searching: () => 'Buscando...',
        },
    });

    usersSelect2.val(selectedUsers.value.map(String)).trigger('change.select2');
    usersSelect2.on('change.calendarUsers', handleUsersSelect2Change);
    usersSelect2.on('select2:open.calendarUsers', () => {
        const searchField = document.querySelector<HTMLInputElement>(
            '.select2-container--open .select2-search__field',
        );
        searchField?.focus();
    });
};

const clearSelectedUsers = () => {
    if (!usersSelect2) {
        handleUserChange([]);
        return;
    }

    usersSelect2.val(null).trigger('change');
};

watch(users, async () => {
    await nextTick();
    await initUsersSelect2();
});

const handleEventDidMount = (info: any) => {
    try {
        const data = info.event.extendedProps;

        // Handle holiday events
        if (data?.isHoliday) {
            tippy(info.el, {
                content: `
                    <div style="font-size:18px">
                        <strong>🎉 ${info.event.title}</strong>
                    </div>
                `,
                allowHTML: true,
            });
            return;
        }

        if (!data?.user) return;

        tippy(info.el, {
            content: `
                <div style="font-size:18px">
                    <strong>${data.user.name}</strong><br/>
                    Tipo: ${data.type.name}<br/>
                    Estado: ${data.status}<br/>
                    Inicio: ${new Date(data.start_datetime).toLocaleDateString('es-CO')}<br/>
                    Fin: ${new Date(data.end_datetime).toLocaleDateString('es-CO')}
                </div>
            `,
            allowHTML: true,
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
        toast.success('Evento actualizado');
    } catch (e) {
        info.revert();
        toast.error('Error al mover');
    }
};

const handleEventResize = async (info: any) => {
    try {
        await axios.put(`/absences/${info.event.id}`, {
            start_datetime: info.event.start,
            end_datetime: info.event.end,
        });
        toast.success('Duración actualizada');
    } catch (e) {
        info.revert();
        toast.error('Error al cambiar tamaño');
    }
};

const canEditEvent = (event: any) => {
    if (isAdmin.value) return true;
    return event.extendedProps.status === 'pendiente';
};

const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: isMobile.value ? 'listWeek' : 'dayGridMonth',
    selectable: true,
    editable: true,
    events: fetchEvents,
    select: handleDateSelect,
    eventClick: handleEventClick,
    eventDrop: handleEventDrop,
    eventResize: handleEventResize,
    eventDidMount: handleEventDidMount,
    eventAllow: canEditEvent,
    height: 'auto',
    dayMaxEvents: isMobile.value ? 2 : true,
    headerToolbar: isMobile.value
        ? {
              left: 'prev,next',
              center: 'title',
              right: 'today',
          }
        : {
              left: 'prev,next today',
              center: 'title',
              right: 'dayGridMonth,timeGridWeek,listWeek',
          },
    locale: 'es',
    buttonText: {
        today: 'Hoy',
        month: 'Mes',
        week: 'Semana',
        day: 'Día',
        list: 'Lista',
    },
}));
</script>

<template>
    <AppLayout>
        <div class="space-y-4 p-3 sm:p-4">
            <!-- Header -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 dark:text-gray-100"
                    >
                        Calendario de Ausencias
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Visualiza y gestiona las ausencias del equipo
                    </p>
                </div>
            </div>

            <!-- Filters Row: Feriados + User Select (same level) -->
            <div class="calendar-filters flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                <!-- Country selector for holidays -->
                <div class="flex items-center justify-between gap-2 sm:justify-start">
                    <label class="text-sm text-gray-600 dark:text-gray-400">
                        Feriados:
                    </label>
                    <select
                        v-model="selectedCountry"
                        class="h-10 w-48 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
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

                <!-- User Select Searchable -->
                <div class="calendar-users-filter flex items-center justify-between gap-2 sm:justify-start">
                    <label class="text-sm text-gray-600 dark:text-gray-400">
                        Usuarios:
                    </label>
                    <div class="relative w-full sm:w-56">
                        <select
                            ref="usersSelectRef"
                            multiple
                            class="calendar-users-select"
                        >
                            <option
                                v-for="user in users"
                                :key="user.id"
                                :value="user.id"
                            >
                                {{ user.name }}
                            </option>
                        </select>
                    </div>
                    <button
                        v-if="selectedUsers.length"
                        @click="clearSelectedUsers"
                        class="text-sm text-red-500 hover:text-red-700 dark:text-red-400"
                    >
                        ✕
                    </button>
                </div>

                <!--
                <div class="flex items-center gap-1 overflow-x-auto">
                    <button
                        @click="handleUserChange([])"
                        class="flex-shrink-0 rounded-full bg-blue-600 px-3 py-1 text-xs font-medium text-white transition-colors"
                    >
                        Todos
                    </button>
                    <button
                        v-for="user in users.slice(0, 5)"
                        :key="user.id"
                        @click="handleUserChange([user.id])"
                        class="flex-shrink-0 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300"
                    >
                        {{ user.name.split(' ')[0] }}
                    </button>
                </div>
                -->
            </div>

            <!-- Legend -->
            <div class="grid grid-cols-2 gap-2 text-sm sm:flex sm:flex-wrap sm:items-center sm:gap-4">
                <div class="flex items-center gap-2">
                    <div class="h-3 w-3 rounded-full bg-amber-500"></div>
                    <span class="text-gray-600 dark:text-gray-400"
                        >Pendiente</span
                    >
                </div>
                <div class="flex items-center gap-2">
                    <div class="h-3 w-3 rounded-full bg-green-500"></div>
                    <span class="text-gray-600 dark:text-gray-400"
                        >Aprobado</span
                    >
                </div>
                <div class="flex items-center gap-2">
                    <div class="h-3 w-3 rounded-full bg-red-500"></div>
                    <span class="text-gray-600 dark:text-gray-400"
                        >Rechazado</span
                    >
                </div>
                <div class="flex items-center gap-2">
                    <div class="h-3 w-3 rounded-full bg-amber-200"></div>
                    <span class="text-gray-600 dark:text-gray-400"
                        >Festivo</span
                    >
                </div>
            </div>

            <!-- Calendar -->
            <div class="rounded-xl border border-gray-200 bg-white p-2 sm:p-4 dark:border-gray-700 dark:bg-gray-800">
                <FullCalendar :options="calendarOptions" ref="calendarRef" />
            </div>
        </div>

        <!-- Modal -->
        <AbsenceModal
            :show="showModal"
            :mode="modalMode"
            :absence="selectedAbsence"
            :selectedRange="selectedRange"
            :users="users"
            :isAdmin="isAdmin"
            @close="showModal = false"
            @saved="refreshCalendar"
        />
    </AppLayout>
</template>

<style>
.fc {
    --fc-border-color: #e5e7eb;
    --fc-button-text-color: #374151;
    --fc-button-bg-color: #f3f4f6;
    --fc-button-border-color: #d1d5db;
    --fc-button-hover-bg-color: #e5e7eb;
    --fc-button-hover-border-color: #d1d5db;
    --fc-button-active-bg-color: #d1d5db;
    --fc-today-bg-color: #eff6ff;
    --fc-event-border-color: transparent;
}

.fc .fc-button {
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
}

.fc .fc-toolbar-title {
    font-size: 1.25rem;
    font-weight: 600;
}

.fc .fc-daygrid-day-number {
    padding: 0.5rem;
}

.fc-event {
    cursor: pointer;
    border-radius: 0.25rem;
    padding: 2px 4px;
}

@media (max-width: 640px) {
    .fc .fc-toolbar {
        gap: 0.5rem;
    }

    .fc .fc-toolbar.fc-header-toolbar {
        flex-direction: column;
        align-items: stretch;
    }

    .fc .fc-toolbar-title {
        font-size: 1rem;
        text-align: center;
    }

    .fc .fc-button {
        padding: 0.375rem 0.5rem;
        font-size: 0.75rem;
    }
}
</style>
