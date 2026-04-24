<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { usePage } from '@inertiajs/vue3';
import ConfirmDialog from './ConfirmDialog.vue';
import { includesNormalized } from '@/lib/search';

interface User {
    id: number;
    name: string;
    first_name: string;
    last_name: string;
    identification: string | null;
    gender: string | null;
    phone: string | null;
    email: string;
    role: string;
    is_active: boolean;
    birth_date: string | null;
    hire_date: string | null;
    photo_path: string | null;
    photo_url?: string;
    allocated: number;
    used: number;
    available: number;
    area_id: number | null;
    area_name: string | null;
}

interface VacationYear {
    id?: number;
    year: number;
    allocated_days: number;
    used_days: number;
    expires_at: string;
}

interface Area {
    id: number;
    name: string;
    color: string;
}

interface Role {
    id: number;
    name: string;
    display_name: string;
    description: string | null;
    color: string;
    is_system: boolean;
    is_active: boolean;
    display_order: number;
    user_count: number;
}

const toast = useToast();
const page = usePage();
const isSuperAdmin = computed(
    () => page.props.auth?.user?.role === 'superadmin',
);

const users = ref<User[]>([]);
const areas = ref<Area[]>([]);
const roles = ref<Role[]>([]);
const loading = ref(false);
const importing = ref(false);
const showModal = ref(false);
const showRolesModal = ref(false);
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
    role: 'colaborador' as string,
    birth_date: '',
    hire_date: '',
    photo: null as File | null,
    area_id: null as number | null,
});

// Role form state
const roleForm = ref({
    name: '',
    display_name: '',
    description: '',
    color: '#6B7280',
    is_active: true,
    display_order: 0,
});
const editingRole = ref<Role | null>(null);

const search = ref('');
const filterRole = ref('');
const importInput = ref<HTMLInputElement | null>(null);

const filteredUsers = computed(() => {
    return users.value.filter((u) => {
        const matchesSearch =
            includesNormalized(u.name, search.value) ||
            includesNormalized(u.email, search.value) ||
            includesNormalized(u.identification, search.value);
        const matchesRole = !filterRole.value || u.role === filterRole.value;
        return matchesSearch && matchesRole;
    });
});

const loadUsers = async () => {
    loading.value = true;
    try {
        const promises = [
            axios.get('/gestion-usuarios/data'),
            axios.get('/api/areas'),
        ];

        // Solo superadmin carga roles
        if (isSuperAdmin.value) {
            promises.push(axios.get('/admin/roles'));
        }

        const results = await Promise.all(promises);
        users.value = results[0].data;
        areas.value = results[1].data.areas || [];

        if (isSuperAdmin.value && results[2]) {
            roles.value = results[2].data;
        }
    } catch (e) {
        console.error(e);
        toast.error('Error cargando datos');
    } finally {
        loading.value = false;
    }
};

// Funciones para gestionar roles (solo superadmin)
const loadRoles = async () => {
    try {
        const res = await axios.get('/admin/roles');
        roles.value = res.data;
    } catch (e) {
        toast.error('Error cargando roles');
    }
};

const openRolesModal = () => {
    showRolesModal.value = true;
    loadRoles();
};

const saveRole = async () => {
    try {
        if (editingRole.value) {
            await axios.put(
                `/admin/roles/${editingRole.value.id}`,
                roleForm.value,
            );
            toast.success('Rol actualizado');
        } else {
            await axios.post('/admin/roles', roleForm.value);
            toast.success('Rol creado');
        }
        loadRoles();
        resetRoleForm();
    } catch (e: any) {
        if (e.response?.data?.message) {
            toast.error(e.response.data.message);
        } else {
            toast.error('Error guardando rol');
        }
    }
};

const deleteRole = async (role: Role) => {
    if (!confirm(`¿Eliminar el rol "${role.display_name}"?`)) return;

    try {
        await axios.delete(`/admin/roles/${role.id}`);
        toast.success('Rol eliminado');
        loadRoles();
    } catch (e: any) {
        if (e.response?.data?.message) {
            toast.error(e.response.data.message);
        } else {
            toast.error('Error eliminando rol');
        }
    }
};

const editRole = (role: Role) => {
    editingRole.value = role;
    roleForm.value = {
        name: role.name,
        display_name: role.display_name,
        description: role.description || '',
        color: role.color,
        is_active: role.is_active,
        display_order: role.display_order,
    };
};

const resetRoleForm = () => {
    editingRole.value = null;
    roleForm.value = {
        name: '',
        display_name: '',
        description: '',
        color: '#6B7280',
        is_active: true,
        display_order: 0,
    };
};

const openCreate = () => {
    modalMode.value = 'create';
    selectedUser.value = null;
    form.value = {
        first_name: '',
        last_name: '',
        identification: '',
        gender: '',
        phone: '',
        email: '',
        password: '',
        role: 'colaborador',
        birth_date: '',
        hire_date: '',
        photo: null,
        area_id: null,
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
        gender: user.gender || '',
        phone: user.phone || '',
        email: user.email,
        password: '',
        role: user.role as 'admin' | 'colaborador',
        birth_date: user.birth_date || '',
        hire_date: user.hire_date || '',
        photo: null,
        area_id: user.area_id || null,
    };
    showModal.value = true;
};

const openView = (user: User) => {
    modalMode.value = 'view';
    selectedUser.value = user;
    showModal.value = true;
};

const downloadImportTemplate = () => {
    window.location.href = '/admin/users/import/template';
};

const triggerImportDialog = () => {
    importInput.value?.click();
};

const handleImportUsers = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;
    if (!file) return;

    importing.value = true;
    try {
        const formData = new FormData();
        formData.append('file', file);

        const response = await axios.post('/admin/users/import', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        const result = response.data;
        toast.success(
            `Importación completada. Creados: ${result.created}, actualizados: ${result.updated}, omitidos: ${result.skipped}`,
        );

        if (Array.isArray(result.errors) && result.errors.length > 0) {
            result.errors.slice(0, 5).forEach((error: string) => toast.warning(error));
            if (result.errors.length > 5) {
                toast.warning(`Y ${result.errors.length - 5} errores más en la importación.`);
            }
        }

        await loadUsers();
    } catch (e: any) {
        if (e.response?.data?.errors) {
            Object.values(e.response.data.errors)
                .flat()
                .forEach((msg) => toast.error(String(msg)));
        } else {
            toast.error('Error en el cargue masivo de usuarios');
        }
    } finally {
        importing.value = false;
        target.value = '';
    }
};

const saveUser = async () => {
    try {
        const formData = new FormData();
        Object.entries(form.value).forEach(([key, value]) => {
            if (key === 'photo' && value) {
                formData.append('photo', value as File);
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
                .forEach((msg) => toast.error(String(msg)));
        } else {
            toast.error('Error guardando usuario');
        }
    }
};

const deleteUser = async (user: User) => {
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
        <input
            ref="importInput"
            type="file"
            class="hidden"
            accept=".xlsx,.xls,.csv"
            @change="handleImportUsers"
        />

        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Gestión de Usuarios
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Administra empleados y asignación de vacaciones
                </p>
            </div>
            <button @click="openCreate" class="btn-primary">
                <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nuevo Usuario
            </button>

            <!-- Botón gestión de roles (solo superadmin) -->
            <button v-if="isSuperAdmin" @click="openRolesModal" class="btn-secondary">
                <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Gestionar Roles
            </button>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                        Cargue masivo de usuarios
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        El archivo debe incluir: nombre, apellidos, identificación, género, correo,
                        número de celular, área, rol, fecha de nacimiento y fecha de contratación.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button class="btn-secondary" @click="downloadImportTemplate">
                        Descargar archivo de ejemplo
                    </button>
                    <button class="btn-primary" :disabled="importing" @click="triggerImportDialog">
                        {{ importing ? 'Importando...' : 'Subir cargue masivo' }}
                    </button>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row">
            <div class="relative h-10 w-full sm:flex-1">
                <input v-model="search" type="text" placeholder="Buscar por nombre, email o identificación..."
                    autocomplete="off" class="input h-10 w-full pl-10" />
            </div>

            <select v-model="filterRole" class="input h-10 w-full sm:flex-1">
                <option value="">Todos los roles</option>
                <template v-if="roles.length > 0">
                    <option v-for="role in roles" :key="role.id" :value="role.name">
                        {{ role.display_name }}
                    </option>
                </template>
                <template v-else>
                    <option value="admin">Administradores</option>
                    <option value="colaborador">Colaboradores</option>
                </template>
            </select>
        </div>

        <!-- Users Table -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">
                                Usuario
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">
                                Identificación
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">
                                Teléfono
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">
                                Rol
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">
                                Activo
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">
                                Área
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">
                                Asignados
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">
                                Usados
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">
                                Disponibles
                            </th>
                            <th
                                class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr v-if="loading" class="text-center">
                            <td colspan="10" class="py-8 text-gray-500">
                                Cargando...
                            </td>
                        </tr>
                        <tr v-else-if="filteredUsers.length === 0" class="text-center">
                            <td colspan="10" class="py-8 text-gray-500">
                                No hay usuarios
                            </td>
                        </tr>
                        <tr v-for="user in filteredUsers" :key="user.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-600">
                                        <img v-if="user.photo_url" :src="user.photo_url"
                                            class="h-10 w-10 rounded-full object-cover" />
                                        <span v-else class="text-lg font-medium text-gray-600 dark:text-gray-300">{{
                                            user.name
                                                .charAt(0)
                                                .toUpperCase()
                                        }}</span>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ user.name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ user.email }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                {{ user.identification || '-' }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                {{ user.phone || '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <span :class="[
                                    'rounded-full px-2.5 py-0.5 text-xs font-medium',
                                    getRoleBadge(user.role),
                                ]">
                                    {{
                                        user.role === 'admin'
                                            ? 'Admin'
                                            : 'Colaborador'
                                    }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    v-if="user.is_active"
                                    class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
                                >
                                    Sí
                                </span>
                                <span
                                    v-else
                                    class="rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-medium text-rose-700 dark:bg-rose-900/30 dark:text-rose-300"
                                >
                                    No
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="user.area_name"
                                    class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium" :style="{
                                        backgroundColor:
                                            areas.find(
                                                (a) => a.id === user.area_id,
                                            )?.color + '20',
                                        color: areas.find(
                                            (a) => a.id === user.area_id,
                                        )?.color,
                                    }">
                                    {{ user.area_name }}
                                </span>
                                <span v-else class="text-xs text-gray-400">Sin área</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                {{ user.allocated }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                {{ user.used }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ user.available
                                    }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="adjustDays(user, 1)"
                                        class="btn-icon bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400"
                                        title="+1 día">
                                        +
                                    </button>
                                    <button @click="adjustDays(user, -1)"
                                        class="btn-icon bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400"
                                        title="-1 día">
                                        -
                                    </button>
                                    <button @click="openView(user)" class="btn-icon" title="Ver">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button @click="openEdit(user)" class="btn-icon" title="Editar">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <ConfirmDialog title="Eliminar usuario" :description="'¿Está seguro de que desea eliminar el usuario ' +
                                        user.name +
                                        '? Esta acción no se puede deshacer.'
                                        " confirm-text="Eliminar" cancel-text="Cancelar" variant="destructive"
                                        @confirm="deleteUser(user)">
                                        <template #trigger>
                                            <button
                                                class="btn-icon text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                                                title="Eliminar">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </template>
                                    </ConfirmDialog>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-900">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                        {{
                            modalMode === 'create'
                                ? 'Nuevo Usuario'
                                : modalMode === 'edit'
                                    ? 'Editar Usuario'
                                    : 'Detalles del Usuario'
                        }}
                    </h2>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Nombre</label>
                            <input v-model="form.first_name" type="text" class="input"
                                :disabled="modalMode === 'view'" />
                        </div>
                        <div>
                            <label class="label">Apellido</label>
                            <input v-model="form.last_name" type="text" class="input"
                                :disabled="modalMode === 'view'" />
                        </div>
                    </div>

                    <div>
                        <label class="label">Identificación</label>
                        <input v-model="form.identification" type="text" class="input"
                            :disabled="modalMode === 'view'" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Teléfono</label>
                            <input v-model="form.phone" type="text" class="input" :disabled="modalMode === 'view'" />
                        </div>
                        <div>
                            <label class="label">Email</label>
                            <input v-model="form.email" type="email" class="input" :disabled="modalMode === 'view'" />
                        </div>
                    </div>

                    <div v-if="modalMode !== 'view'">
                        <label class="label">Contraseña
                            {{
                                modalMode === 'edit'
                                    ? '(dejar vacío para mantener)'
                                    : ''
                            }}</label>
                        <input v-model="form.password" type="password" class="input"
                            :required="modalMode === 'create'" />
                    </div>

                    <div>
                        <label class="label">Rol</label>
                        <select v-model="form.role" class="input" :disabled="modalMode === 'view'">
                            <template v-if="roles.length > 0">
                                <option v-for="role in roles" :key="role.id" :value="role.name">
                                    {{ role.display_name }}
                                </option>
                            </template>
                            <template v-else>
                                <option value="colaborador">Colaborador</option>
                                <option value="admin">Administrador</option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="label">Área</label>
                        <select v-model="form.area_id" class="input" :disabled="modalMode === 'view'">
                            <option :value="null">Sin área asignada</option>
                            <option v-for="area in areas" :key="area.id" :value="area.id">
                                {{ area.name }}
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Fecha de Nacimiento</label>
                            <input v-model="form.birth_date" type="date" class="input"
                                :disabled="modalMode === 'view'" />
                        </div>
                        <div>
                            <label class="label">Fecha de Contratación</label>
                            <input v-model="form.hire_date" type="date" class="input"
                                :disabled="modalMode === 'view'" />
                        </div>
                    </div>

                    <div v-if="modalMode !== 'view'">
                        <label class="label">Foto</label>
                        <input type="file" accept="image/*" @change="
                            (e) =>
                            (form.photo =
                                (e.target as HTMLInputElement)
                                    .files?.[0] || null)
                        " class="input" />
                    </div>

                    <div v-if="modalMode === 'view' && selectedUser" class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                        <h3 class="mb-2 font-medium text-gray-900 dark:text-gray-100">
                            Información adicional
                        </h3>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div>
                                <span class="text-gray-500">Fecha de nacimiento:</span>
                                {{ formatDate(selectedUser.birth_date) }}
                            </div>
                            <div>
                                <span class="text-gray-500">Fecha de contratación:</span>
                                {{ formatDate(selectedUser.hire_date) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button @click="showModal = false" class="btn-secondary">
                        {{ modalMode === 'view' ? 'Cerrar' : 'Cancelar' }}
                    </button>
                    <button v-if="modalMode !== 'view'" @click="saveUser" class="btn-primary">
                        {{ modalMode === 'create' ? 'Crear' : 'Guardar' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Gestión de Roles (solo superadmin) -->
    <div v-if="showRolesModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @click.self="showRolesModal = false">
        <div class="w-full max-w-2xl rounded-xl bg-white p-6 dark:bg-gray-800">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                    Gestión de Roles
                </h2>
                <button @click="showRolesModal = false" class="text-gray-500 hover:text-gray-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Formulario para crear/editar rol -->
            <div class="mb-6 rounded-lg bg-gray-50 p-4 dark:bg-gray-700">
                <h3 class="mb-3 font-medium text-gray-900 dark:text-gray-100">
                    {{ editingRole ? 'Editar Rol' : 'Crear Nuevo Rol' }}
                </h3>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label">Nombre (slug)</label>
                        <input v-model="roleForm.name" type="text" class="input" placeholder="ej: supervisor"
                            :disabled="!!editingRole" />
                    </div>
                    <div>
                        <label class="label">Nombre Display</label>
                        <input v-model="roleForm.display_name" type="text" class="input" placeholder="ej: Supervisor" />
                    </div>
                    <div class="col-span-2">
                        <label class="label">Descripción</label>
                        <input v-model="roleForm.description" type="text" class="input"
                            placeholder="Descripción del rol" />
                    </div>
                    <div>
                        <label class="label">Color</label>
                        <input v-model="roleForm.color" type="color"
                            class="h-10 w-full rounded border border-gray-300" />
                    </div>
                    <div>
                        <label class="label">Orden</label>
                        <input v-model.number="roleForm.display_order" type="number" class="input" min="0" />
                    </div>
                    <div class="col-span-2 flex items-center gap-2">
                        <input v-model="roleForm.is_active" type="checkbox" id="role_is_active"
                            class="h-4 w-4 rounded border-gray-300" />
                        <label for="role_is_active" class="text-sm text-gray-700 dark:text-gray-300">
                            Rol activo
                        </label>
                    </div>
                </div>
                <div class="mt-3 flex gap-2">
                    <button @click="saveRole" class="btn-primary text-sm">
                        {{ editingRole ? 'Actualizar' : 'Crear' }}
                    </button>
                    <button v-if="editingRole" @click="resetRoleForm" class="btn-secondary text-sm">
                        Cancelar
                    </button>
                </div>
            </div>

            <!-- Lista de roles existentes -->
            <div>
                <h3 class="mb-3 font-medium text-gray-900 dark:text-gray-100">
                    Roles Existentes
                </h3>
                <div class="space-y-2">
                    <div v-for="role in roles" :key="role.id"
                        class="flex items-center justify-between rounded-lg border border-gray-200 p-3 dark:border-gray-600">
                        <div class="flex items-center gap-3">
                            <div class="h-4 w-4 rounded-full" :style="{ backgroundColor: role.color }"></div>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ role.display_name }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ role.name }} ·
                                    {{ role.user_count }} usuarios
                                    <span v-if="role.is_system" class="text-blue-600">
                                        · Sistema
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span v-if="role.is_active"
                                class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                Activo
                            </span>
                            <span v-else class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-800">
                                Inactivo
                            </span>
                            <button v-if="!role.is_system" @click="editRole(role)"
                                class="rounded p-1 text-gray-500 hover:bg-gray-100">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </button>
                            <button v-if="!role.is_system && role.user_count === 0" @click="deleteRole(role)"
                                class="rounded p-1 text-red-500 hover:bg-red-50">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
