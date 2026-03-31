<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import axios from 'axios';
import AbsenceModal from '@/components/AbsenceModal.vue';
import { useToast } from 'vue-toastification';
import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';

const calendarRef = ref(null);
const showModal = ref(false);
const modalMode = ref('create');
const selectedRange = ref(null);
const selectedAbsence = ref(null);
const selectedUser = ref<number | null>(null);
const users = ref<
    { id: number; name: string; identification: string; email: string }[]
>([]);
const isAdmin = ref(false);
const searchQuery = ref('');
const toast = useToast();
const selectedCountry = ref('CO');
const holidays = ref<{ date: string; title: string }[]>([]);
const countries = ref<{ code: string; name: string }[]>([]);

const filteredUsers = ref<
    { id: number; name: string; identification: string; email: string }[]
>([]);

watch(searchQuery, (query) => {
    if (!query.trim()) {
        filteredUsers.value = users.value;
    } else {
        const q = query.toLowerCase();
        filteredUsers.value = users.value.filter(
            (u) =>
                u.name.toLowerCase().includes(q) ||
                u.identification?.toLowerCase().includes(q) ||
                u.email.toLowerCase().includes(q),
        );
    }
});

onMounted(async () => {
    try {
        const userRes = await axios.get('/me');
        const user = userRes.data;
        isAdmin.value = user?.role === 'admin' || user?.is_admin;

        // Load users list for filtering (only admins need full list)
        const usersRes = await axios.get('/users-list');
        users.value = usersRes.data.map((u: any) => ({
            id: u.id,
            name: u.name,
            identification: u.identification || '',
            email: u.email || '',
        }));
        filteredUsers.value = users.value;

        // Load countries and holidays
        await loadCountries();
        await loadHolidays(new Date().getFullYear());
    } catch (e) {
        console.error('Error loading data:', e);
    }
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

        if (selectedUser.value) {
            params.user_id = selectedUser.value;
        }

        const res = await axios.get('/absences', { params });

        const absenceEvents = res.data.map((item: any) => ({
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
            backgroundColor: '#green',
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

const handleUserChange = (userId: number | null) => {
    selectedUser.value = userId;
    refreshCalendar();
};

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

const calendarOptions = {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
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
    headerToolbar: {
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
};
</script>

<template>
    <AppLayout>
        <div class="space-y-4 p-4">
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

            <!-- Search and Filter -->
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
            >
                <!-- Search by name, identification or email -->
                <div class="max-w-md flex-1">
                    <div class="relative">
                        <svg
                            class="absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Buscar por nombre, identificación o correo..."
                            class="w-full rounded-lg border border-gray-300 py-2 pr-4 pl-10 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                        />
                    </div>
                </div>

                <!-- Country selector for holidays -->
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600 dark:text-gray-400">
                        Feriados:
                    </label>
                    <select
                        v-model="selectedCountry"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
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

                <!-- User Filter -->
                <div class="flex items-center gap-2 overflow-x-auto pb-2">
                    <button
                        @click="handleUserChange(null)"
                        :class="[
                            'flex-shrink-0 rounded-full px-4 py-2 text-sm font-medium transition-colors',
                            selectedUser === null
                                ? 'bg-blue-600 text-white'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300',
                        ]"
                    >
                        Todos
                    </button>
                    <button
                        v-for="user in filteredUsers"
                        :key="user.id"
                        @click="handleUserChange(user.id)"
                        :class="[
                            'flex-shrink-0 rounded-full px-4 py-2 text-sm font-medium transition-colors',
                            selectedUser === user.id
                                ? 'bg-blue-600 text-white'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300',
                        ]"
                    >
                        {{ user.name }}
                    </button>
                </div>
            </div>

            <!-- Legend -->
            <div class="flex flex-wrap items-center gap-4 text-sm">
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
                    <span class="text-gray-100 dark:text-gray-400"
                        >Festivo</span
                    >
                </div>
            </div>

            <!-- Calendar -->
            <div
                class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
            >
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
</style>
