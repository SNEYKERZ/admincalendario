<script setup>
import { ref, onMounted } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import axios from 'axios'

// eventos del calendario
const events = ref([])

// cargar eventos desde backend
const fetchEvents = async (info, successCallback, failureCallback) => {
    try {
        const response = await axios.get('/absences', {
            params: {
                start: info.startStr,
                end: info.endStr
            }
        })

        const data = response.data.map(item => ({
            id: item.id,
            title: `${item.user.name} - ${item.type.name}`,
            start: item.start_datetime,
            end: item.end_datetime,
            color: getColor(item.type.name),
            extendedProps: item
        }))

        successCallback(data)

    } catch (error) {
        console.error(error)
        failureCallback(error)
    }
}

// colores por tipo
const getColor = (item) => {
    switch (item.status) {
        case 'pendiente':
            return '#9ca3af' // gris
        case 'aprobado':
            return '#22c55e' // verde
        case 'rechazado':
            return '#ef4444' // rojo
    }
}

// click en fecha (crear)
const handleDateClick = (info) => {
    console.log('Crear ausencia en:', info.dateStr)

    // aquí luego abrimos modal
}

// click en evento (ver detalle)
const handleEventClick = (info) => {
    console.log('Evento:', info.event.extendedProps)
}

// configuración calendario
const calendarOptions = ref({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    selectable: true,
    editable: false,
    events: fetchEvents,
    dateClick: handleDateClick,
    eventClick: handleEventClick,
    height: 'auto',

    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
    }
})
</script>

<template>
    <div class="p-4 bg-white rounded-xl shadow">
        <FullCalendar :options="calendarOptions" />
    </div>
</template>