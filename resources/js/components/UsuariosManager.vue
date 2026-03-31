<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

interface User {
    id: number;
    name: string;
    first_name: string;
    last_name: string;
    identification: string | null;
    phone: string | null;
    email: string;
    role: string;
    birth_date: string | null;
    hire_date: string | null;
    photo_path: string | null;
    photo_url?: string;
    allocated: number;
    used: number;
    available: number;
}

interface VacationYear {
    id?: number;
    year: number;
    allocated_days: number;
    used_days: number;
    expires_at: string;
}

const toast = useToast();

const users = ref<User[]>([]);
const loading = ref(false);
const showModal = ref(false);
const modalMode = ref<'create' | 'edit' | 'view'>('create');
const selectedUser = ref<User | null>(null);

// Form state
const form = ref({
    first_name: '',
    last_name: '',
    identification: '',
    phone: '',
    email: '',
    password: '',
    role: 'colaborador' as 'admin' | 'colaborador',
    birth_date: '',
    hire_date: '',
    photo: null as File | null,
});

const search = ref('');
const filterRole = ref('');

const filteredUsers = computed(() => {
    return users.value.filter((u) => {
        const matchesSearch =
            !search.value ||
            u.name.toLowerCase().includes(search.value.toLowerCase()) ||
            u.email.toLowerCase().includes(search.value.toLowerCase());
        const matchesRole = !filterRole.value || u.role === filterRole.value;
        return matchesSearch && matchesRole;
    });
});

const loadUsers = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/gestion-usuarios/data');
        users.value = res.data;
    } catch (e) {
        console.error(e);
        toast.error('Error cargando usuarios');
    } finally {
        loading.value = false;
    }
};

const openCreate = () => {
    modalMode.value = 'create';
    selectedUser.value = null;
    form.value = {
        first_name: '',
        last_name: '',
        identification: '',
        phone: '',
        email: '',
        password: '',
        role: 'colaborador',
        birth_date: '',
        hire_date: '',
        photo: null,
    };
    showModal.value = true;
};

const openEdit = (user: User) => {
    modalMode.value = 'edit';
    selectedUser.value = user;
    form.value = {
        first_name: user.first_name,
        last_name: user.last_name,
        identification: user.identification || '',
        phone: user.phone || '',
        email: user.email,
        password: '',
        role: user.role as 'admin' | 'colaborador',
        birth_date: user.birth_date || '',
        hire_date: user.hire_date || '',
        photo: null,
    };
    showModal.value = true;
};

const openView = (user: User) => {
    modalMode.value = 'view';
    selectedUser.value = user;
    showModal.value = true;
};

const saveUser = async () => {
    try {
        const formData = new FormData();
        Object.entries(form.value).forEach(([key, value]) => {
            if (key === 'photo' && value) {
                formData.append('photo', value);
            } else if (value !== null && value !== '') {
                formData.append(key, String(value));
            }
        });

        if (modalMode.value === 'create') {
            await axios.post('/admin/users', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            toast.success('Usuario creado');
        } else if (selectedUser.value) {
            await axios.put(`/admin/users/${selectedUser.value.id}`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            toast.success('Usuario actualizado');
        }

        showModal.value = false;
        loadUsers();
    } catch (e: any) {
        if (e.response?.data?.errors) {
            Object.values(e.response.data.errors)
                .flat()
                .forEach((msg: string) => toast.error(msg));
        } else {
            toast.error('Error guardando usuario');
        }
    }
};

const deleteUser = async (user: User) => {
    if (!confirm(`¿Eliminar usuario "${user.name}"?`)) return;

    try {
        await axios.delete(`/admin/users/${user.id}`);
        toast.success('Usuario eliminado');
        loadUsers();
    } catch {
        toast.error('Error eliminando usuario');
    }
};

const adjustDays = async (user: User, days: number) => {
    try {
        await axios.post(`/gestion-usuarios/${user.id}/adjust`, { days });
        toast.success('Días actualizados');
        loadUsers();
    } catch {
        toast.error('Error actualizando días');
    }
};

onMounted(loadUsers);

const formatDate = (date: string | null) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-CO');
};

const getRoleBadge = (role: string) => {
    return role === 'admin'
        ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300'
        : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
};
</script>

<template>
    <div class="space-y-4">
        <!-- Header -->
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Gestión de Usuarios
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Administra empleados y asignación de vacaciones
                </p>
            </div>
            <button @click="openCreate" class="btn-primary">
                <svg
                    class="mr-2 h-5 w-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 4v16m8-8H4"
                    />
                </svg>
                Nuevo Usuario
            </button>
        </div>

        <!-- Filters -->
        <div class="flex gap-4">
            <div class="flex-1">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Buscar por nombre o email..."
                    class="input"
                />
            </div>
            <select v-model="filterRole" class="input w-auto">
                <option value="">Todos los roles</option>
                <option value="admin">Administradores</option>
                <option value="colaborador">Colaboradores</option>
            </select>
        </div>

        <!-- Users Table -->
        <div
            class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
        >
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                            >
                                Usuario
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                            >
                                Identificación
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                            >
                                Teléfono
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                            >
                                Rol
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                            >
                                Asignados
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                            >
                                Usados
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                            >
                                Disponibles
                            </th>
                            <th
                                class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                            >
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-gray-100 dark:divide-gray-700"
                    >
                        <tr v-if="loading" class="text-center">
                            <td colspan="8" class="py-8 text-gray-500">
                                Cargando...
                            </td>
                        </tr>
                        <tr
                            v-else-if="filteredUsers.length === 0"
                            class="text-center"
                        >
                            <td colspan="8" class="py-8 text-gray-500">
                                No hay usuarios
                            </td>
                        </tr>
                        <tr
                            v-for="user in filteredUsers"
                            :key="user.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/30"
                        >
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-600"
                                    >
                                        <img
                                            v-if="user.photo_url"
                                            :src="user.photo_url"
                                            class="h-10 w-10 rounded-full object-cover"
                                        />
                                        <span
                                            v-else
                                            class="text-lg font-medium text-gray-600 dark:text-gray-300"
                                            >{{
                                                user.name
                                                    .charAt(0)
                                                    .toUpperCase()
                                            }}</span
                                        >
                                    </div>
                                    <div>
                                        <div
                                            class="font-medium text-gray-900 dark:text-gray-100"
                                        >
                                            {{ user.name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ user.email }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td
                                class="px-4 py-3 text-gray-600 dark:text-gray-400"
                            >
                                {{ user.identification || '-' }}
                            </td>
                            <td
                                class="px-4 py-3 text-gray-600 dark:text-gray-400"
                            >
                                {{ user.phone || '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="[
                                        'rounded-full px-2.5 py-0.5 text-xs font-medium',
                                        getRoleBadge(user.role),
                                    ]"
                                >
                                    {{
                                        user.role === 'admin'
                                            ? 'Admin'
                                            : 'Colaborador'
                                    }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                {{ user.allocated }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                {{ user.used }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    class="font-bold text-emerald-600 dark:text-emerald-400"
                                    >{{ user.available }}</span
                                >
                            </td>
                            <td class="px-4 py-3">
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    <button
                                        @click="adjustDays(user, 1)"
                                        class="btn-icon bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400"
                                        title="+1 día"
                                    >
                                        +
                                    </button>
                                    <button
                                        @click="adjustDays(user, -1)"
                                        class="btn-icon bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400"
                                        title="-1 día"
                                    >
                                        -
                                    </button>
                                    <button
                                        @click="openView(user)"
                                        class="btn-icon"
                                        title="Ver"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                            />
                                        </svg>
                                    </button>
                                    <button
                                        @click="openEdit(user)"
                                        class="btn-icon"
                                        title="Editar"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                            />
                                        </svg>
                                    </button>
                                    <button
                                        @click="deleteUser(user)"
                                        class="btn-icon text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                                        title="Eliminar"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        >
            <div
                class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-900"
            >
                <div class="mb-4 flex items-center justify-between">
                    <h2
                        class="text-lg font-bold text-gray-900 dark:text-gray-100"
                    >
                        {{
                            modalMode === 'create'
                                ? 'Nuevo Usuario'
                                : modalMode === 'edit'
                                  ? 'Editar Usuario'
                                  : 'Detalles del Usuario'
                        }}
                    </h2>
                    <button
                        @click="showModal = false"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <svg
                            class="h-6 w-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Nombre</label>
                            <input
                                v-model="form.first_name"
                                type="text"
                                class="input"
                                :disabled="modalMode === 'view'"
                            />
                        </div>
                        <div>
                            <label class="label">Apellido</label>
                            <input
                                v-model="form.last_name"
                                type="text"
                                class="input"
                                :disabled="modalMode === 'view'"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="label">Identificación</label>
                        <input
                            v-model="form.identification"
                            type="text"
                            class="input"
                            :disabled="modalMode === 'view'"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Teléfono</label>
                            <input
                                v-model="form.phone"
                                type="text"
                                class="input"
                                :disabled="modalMode === 'view'"
                            />
                        </div>
                        <div>
                            <label class="label">Email</label>
                            <input
                                v-model="form.email"
                                type="email"
                                class="input"
                                :disabled="modalMode === 'view'"
                            />
                        </div>
                    </div>

                    <div v-if="modalMode !== 'view'">
                        <label class="label"
                            >Contraseña
                            {{
                                modalMode === 'edit'
                                    ? '(dejar vacío para mantener)'
                                    : ''
                            }}</label
                        >
                        <input
                            v-model="form.password"
                            type="password"
                            class="input"
                            :required="modalMode === 'create'"
                        />
                    </div>

                    <div>
                        <label class="label">Rol</label>
                        <select
                            v-model="form.role"
                            class="input"
                            :disabled="modalMode === 'view'"
                        >
                            <option value="colaborador">Colaborador</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Fecha de Nacimiento</label>
                            <input
                                v-model="form.birth_date"
                                type="date"
                                class="input"
                                :disabled="modalMode === 'view'"
                            />
                        </div>
                        <div>
                            <label class="label">Fecha de Contratación</label>
                            <input
                                v-model="form.hire_date"
                                type="date"
                                class="input"
                                :disabled="modalMode === 'view'"
                            />
                        </div>
                    </div>

                    <div v-if="modalMode !== 'view'">
                        <label class="label">Foto</label>
                        <input
                            type="file"
                            accept="image/*"
                            @change="
                                (e) =>
                                    (form.photo =
                                        (e.target as HTMLInputElement)
                                            .files?.[0] || null)
                            "
                            class="input"
                        />
                    </div>

                    <div
                        v-if="modalMode === 'view' && selectedUser"
                        class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800"
                    >
                        <h3
                            class="mb-2 font-medium text-gray-900 dark:text-gray-100"
                        >
                            Información adicional
                        </h3>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div>
                                <span class="text-gray-500"
                                    >Fecha de nacimiento:</span
                                >
                                {{ formatDate(selectedUser.birth_date) }}
                            </div>
                            <div>
                                <span class="text-gray-500"
                                    >Fecha de contratación:</span
                                >
                                {{ formatDate(selectedUser.hire_date) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button @click="showModal = false" class="btn-secondary">
                        {{ modalMode === 'view' ? 'Cerrar' : 'Cancelar' }}
                    </button>
                    <button
                        v-if="modalMode !== 'view'"
                        @click="saveUser"
                        class="btn-primary"
                    >
                        {{ modalMode === 'create' ? 'Crear' : 'Guardar' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.input {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 0.25rem;
    background-color: white;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    color: #111827;
}
.label {
    display: block;
    margin-bottom: 0.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
}
.btn-primary {
    display: inline-flex;
    align-items: center;
    border-radius: 0.25rem;
    background-color: #2563eb;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: white;
}
.btn-secondary {
    display: inline-flex;
    align-items: center;
    border: 1px solid #d1d5db;
    border-radius: 0.25rem;
    background-color: white;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
}
.btn-icon {
    display: inline-flex;
    width: 2rem;
    height: 2rem;
    align-items: center;
    justify-content: center;
    border-radius: 0.25rem;
    color: #6b7280;
}
</style>
