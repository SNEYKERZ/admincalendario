<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const users = ref([])
const selectedUser = ref(null)

const emit = defineEmits(['change'])

// seleccionar usuario
const selectUser = (user) => {
    selectedUser.value = user.id
    emit('change', user.id)
}

onMounted(async () => {
    const res = await axios.get('/users-list')
    users.value = res.data
})
</script>

<template>
    <div class="flex gap-3 overflow-x-auto pb-2">

        <!-- TODOS -->
        <button type="button" @click="selectUser({ id: null })" class="cursor-pointer flex flex-col items-center">
            <span aria-hidden="true" class="w-12 h-12 rounded-full bg-gray-300 flex items-center justify-center">
                👥
            </span>
            <span class="text-xs">Todos</span>
        </button>

        <!-- USUARIOS -->
        <button v-for="user in users" :key="user.id" type="button" @click="selectUser(user)"
            class="cursor-pointer flex flex-col items-center">
            <img v-if="user.photo" :src="user.photo" alt="" class="w-12 h-12 rounded-full object-cover border-2" />

            <span v-else aria-hidden="true" class="w-12 h-12 rounded-full bg-blue-500 text-white flex items-center justify-center">
                {{ user.name.charAt(0) }}
            </span>

            <span class="text-xs text-center">
                {{ user.name }}
            </span>

            <span class="text-[10px] text-gray-500">
                {{ user.available_days }} días
            </span>
        </button>

    </div>
</template>
