<script setup>
import { ref, onMounted } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import axios from 'axios'
import AbsenceModal from './AbsenceModal.vue'
import UserFilter from './UserFilter.vue'
import { useToast } from 'vue-toastification'
import tippy from 'tippy.js'
import 'tippy.js/dist/tippy.css'
// refs
const calendarRef = ref(null)
const showModal = ref(false)
const modalMode = ref('create')
const selectedRange = ref(null)
const selectedAbsence = ref(null)
const selectedUser = ref(null)
const users = ref([])
const isAdmin = ref(false)
const toast = useToast()

// detectar usuario
onMounted(async () => {
    const userRes = await axios.get('/me')
    const usersRes = await axios.get('/users-list')

    users.value = usersRes.data

    const user = userRes.data

    isAdmin.value = user?.role === 'admin' || user?.is_admin
})

// colores
const getColor = (status) => {
    switch (status) {
        case 'pendiente': return '#f59e0b'
        case 'aprobado': return '#22c55e'
        case 'rechazado': return '#ef4444'
        default: return '#9ca3af'
    }
}

// eventos
const fetchEvents = async (info, successCallback, failureCallback) => {
    try {
        const res = await axios.get('/absences', {
            params: {
                start: info.startStr,
                end: info.endStr,
                user_id: selectedUser.value
            }
        })

        const data = res.data.map(item => ({
            id: item.id,
            title: `${item.user?.name ?? ''} - ${item.type?.name ?? ''}`,
            start: item.start_datetime,
            end: item.end_datetime,
            color: getColor(item.status),
            extendedProps: item
        }))

        successCallback(data)

    } catch (e) {
        console.error(e)
        failureCallback(e)
    }
}

// crear
const handleDateSelect = (info) => {
    modalMode.value = 'create'
    selectedRange.value = info
    showModal.value = true
}

// ver
const handleEventClick = async (info) => {
    const res = await axios.get(`/absences/${info.event.id}`)

    modalMode.value = 'view'
    selectedAbsence.value = res.data
    showModal.value = true
}

// refresh
const refreshCalendar = () => {
    calendarRef.value?.getApi().refetchEvents()
}

// filtro
const handleUserChange = (userId) => {
    selectedUser.value = userId
    refreshCalendar()
}

// tooltip
const formatDate = (date) => new Date(date).toLocaleString()

const handleEventDidMount = (info) => {
    try {
        const data = info.event.extendedProps

        if (!data?.user) return

        tippy(info.el, {
            content: `
                <div style="font-size:12px">
                    <strong>${data.user.name}</strong><br/>
                    Tipo: ${data.type.name}<br/>
                    Estado: ${data.status}<br/>
                </div>
            `,
            allowHTML: true,
        })
    } catch (e) {
        console.error('Tooltip error', e)
    }
}
// actualizar fechas (drag / resize)
const updateEventDates = async (event, revert) => {
    try {
        await axios.put(`/absences/${event.id}`, {
            start_datetime: event.start,
            end_datetime: event.end,
        })

        toast.success('Actualizado')

    } catch (e) {
        console.error(e)

        //  vuelve atrás si falla
        revert()

        toast.error('Error al actualizar')
    }
}
const handleEventDrop = async (info) => {
    try {
        await axios.put(`/absences/${info.event.id}`, {
            start_datetime: info.event.start,
            end_datetime: info.event.end
        })

        toast.success('Evento actualizado')
    } catch (e) {
        info.revert()
        toast.error('Error al mover')
    }
}

const handleEventResize = async (info) => {
    try {
        await axios.put(`/absences/${info.event.id}`, {
            start_datetime: info.event.start,
            end_datetime: info.event.end
        })

        toast.success('Duración actualizada')
    } catch (e) {
        info.revert()
        toast.error('Error al cambiar tamaño')
    }
}

const canEditEvent = (event) => {
    if (isAdmin.value) return true
    return event.extendedProps.status === 'pendiente'
}

// config
const calendarOptions = {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],

    initialView: 'dayGridMonth',

    selectable: true,
    editable: true, //  IMPORTANTE

    events: fetchEvents,
    select: handleDateSelect,
    eventClick: handleEventClick,

    eventDrop: handleEventDrop,     //  drag
    eventResize: handleEventResize, //  resize

    eventDidMount: handleEventDidMount,
    eventAllow: canEditEvent, //  solo permite mover/resize si se puede editar

    height: 'auto',

}

</script>

<template>
    <div class="p-4 space-y-4">

        <!-- FILTRO -->
        <UserFilter @change="handleUserChange" />

        <!-- CALENDARIO -->
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow p-4">
            <FullCalendar :options="calendarOptions" ref="calendarRef" />
        </div>

        <!-- MODAL -->
        <AbsenceModal :show="showModal" :mode="modalMode" :absence="selectedAbsence" :selectedRange="selectedRange"
            :users="users" :isAdmin="isAdmin" @close="showModal = false" @saved="refreshCalendar" />
    </div>
</template>