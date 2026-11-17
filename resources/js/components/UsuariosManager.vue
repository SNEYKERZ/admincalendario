<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { refDebounced } from '@vueuse/core';
import axios from 'axios';
import { CalendarCheck, FileUp, Search, ShieldCheck, UserCheck, UserPlus, Users } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import PageHeader from '@/components/app/PageHeader.vue';
import SectionCard from '@/components/app/SectionCard.vue';
import StatCard from '@/components/app/StatCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import RolesDialog from '@/components/users/RolesDialog.vue';
import { roleLabel } from '@/components/users/types';
import type { ManagedUser, UserArea, UserFormMode, UserRole } from '@/components/users/types';
import UserFormDialog from '@/components/users/UserFormDialog.vue';
import UserImportDialog from '@/components/users/UserImportDialog.vue';
import UsersTable from '@/components/users/UsersTable.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useNotification } from '@/composables/useNotification';
import { includesNormalized } from '@/lib/search';

const notify = useNotification();
const confirm = useConfirm();
const page = usePage();
const isSuperAdmin = computed(() => (page.props.auth as any)?.user?.role === 'superadmin');

const users = ref<ManagedUser[]>([]);
const areas = ref<UserArea[]>([]);
const roles = ref<UserRole[]>([]);
const loading = ref(false);
const loadingDetailId = ref<number | null>(null);
const adjustingId = ref<number | null>(null);

const showForm = ref(false);
const formMode = ref<UserFormMode>('create');
const selectedUser = ref<ManagedUser | null>(null);
const showImport = ref(false);
const showRoles = ref(false);

const search = ref('');
const debouncedSearch = refDebounced(search, 200);
const filterRole = ref('');

const hasFilters = computed(() => !!search.value || !!filterRole.value);

const filteredUsers = computed(() =>
    users.value.filter((u) => {
        const term = debouncedSearch.value;
        const matchesSearch =
            includesNormalized(u.name, term) ||
            includesNormalized(u.email, term) ||
            includesNormalized(u.identification, term);
        const matchesRole = !filterRole.value || u.role === filterRole.value;
        return matchesSearch && matchesRole;
    }),
);

const stats = computed(() => ({
    total: users.value.length,
    active: users.value.filter((u) => u.is_active).length,
    admins: users.value.filter((u) => u.role === 'admin' || u.role === 'superadmin').length,
    available: users.value.reduce((acc, u) => acc + (Number(u.available) || 0), 0),
}));

const roleFilterOptions = computed(() =>
    roles.value.length > 0
        ? roles.value.map((r) => ({ value: r.name, label: r.display_name }))
        : [
              { value: 'admin', label: 'Administradores' },
              { value: 'colaborador', label: 'Colaboradores' },
          ],
);

const loadUsers = async () => {
    loading.value = users.value.length === 0;
    try {
        const promises = [axios.get('/gestion-usuarios/data'), axios.get('/api/areas')];
        if (isSuperAdmin.value) promises.push(axios.get('/admin/roles'));

        const results = await Promise.all(promises);
        users.value = results[0].data;
        areas.value = results[1].data.areas || [];
        if (isSuperAdmin.value && results[2]) roles.value = results[2].data;
    } catch (e) {
        console.error(e);
        notify.error('No se pudieron cargar los usuarios.');
    } finally {
        loading.value = false;
    }
};

const fetchUserDetail = async (user: ManagedUser): Promise<ManagedUser | null> => {
    loadingDetailId.value = user.id;
    try {
        const res = await axios.get(`/admin/users/${user.id}`);
        return res.data as ManagedUser;
    } catch (e) {
        console.error(e);
        notify.error('No se pudo cargar la información del usuario.');
        return null;
    } finally {
        loadingDetailId.value = null;
    }
};

const openCreate = () => {
    formMode.value = 'create';
    selectedUser.value = null;
    showForm.value = true;
};

const openEdit = (user: ManagedUser) => {
    formMode.value = 'edit';
    selectedUser.value = user;
    showForm.value = true;
};

const openView = async (user: ManagedUser) => {
    const detail = await fetchUserDetail(user);
    if (!detail) return;
    formMode.value = 'view';
    selectedUser.value = detail;
    showForm.value = true;
};

const onSaved = async () => {
    await loadUsers();
    if (formMode.value === 'view' && selectedUser.value) {
        const detail = await fetchUserDetail(selectedUser.value);
        if (detail) selectedUser.value = detail;
    }
};

const deleteUser = async (user: ManagedUser) => {
    const ok = await confirm({
        title: 'Eliminar usuario',
        description: `¿Eliminar a ${user.name}? Se borrarán su acceso y su foto. Esta acción no se puede deshacer.`,
        confirmText: 'Eliminar usuario',
        variant: 'destructive',
    });
    if (!ok) return;
    try {
        await axios.delete(`/admin/users/${user.id}`);
        notify.success('Usuario eliminado');
        await loadUsers();
    } catch (e: any) {
        notify.error(e?.response?.data?.message || 'No se pudo eliminar el usuario.');
    }
};

const adjustDays = async (user: ManagedUser, days: number) => {
    adjustingId.value = user.id;
    try {
        await axios.post(`/gestion-usuarios/${user.id}/adjust`, { days });
        notify.success(days > 0 ? `+1 día para ${user.name}` : `−1 día para ${user.name}`);
        await loadUsers();
    } catch (e: any) {
        notify.error(e?.response?.data?.message || 'No se pudieron actualizar los días.');
    } finally {
        adjustingId.value = null;
    }
};

const clearFilters = () => {
    search.value = '';
    filterRole.value = '';
};

onMounted(loadUsers);
</script>

<template>
    <PageHeader
        title="Usuarios y vacaciones"
        description="Administra a los colaboradores, sus roles, áreas y el saldo de días de vacaciones."
        :icon="Users"
    >
        <template #actions>
            <Button v-if="isSuperAdmin" variant="outline" @click="showRoles = true">
                <ShieldCheck /> Roles
            </Button>
            <Button variant="outline" @click="showImport = true">
                <FileUp /> Importar
            </Button>
            <Button @click="openCreate">
                <UserPlus /> Nuevo usuario
            </Button>
        </template>
    </PageHeader>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <StatCard label="Usuarios" :value="stats.total" :icon="Users" :loading="loading" />
        <StatCard label="Activos" :value="stats.active" :icon="UserCheck" tone="success" :loading="loading" />
        <StatCard label="Administradores" :value="stats.admins" :icon="ShieldCheck" tone="primary" :loading="loading" />
        <StatCard
            label="Días disponibles (total)"
            :value="stats.available"
            :icon="CalendarCheck"
            tone="info"
            :loading="loading"
        />
    </div>

    <SectionCard flush>
        <template #header>
            <div class="flex w-full flex-col gap-2 sm:flex-row sm:items-center">
                <div class="relative w-full sm:max-w-sm">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        v-model="search"
                        type="search"
                        class="pl-9"
                        placeholder="Buscar por nombre, correo o identificación"
                        aria-label="Buscar usuarios"
                        autocomplete="off"
                    />
                </div>
                <select v-model="filterRole" class="input sm:w-52" aria-label="Filtrar por rol">
                    <option value="">Todos los roles</option>
                    <option v-for="opt in roleFilterOptions" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                    </option>
                </select>
                <span
                    v-if="!loading"
                    class="text-[13px] text-muted-foreground sm:ml-auto"
                    aria-live="polite"
                >
                    <Spinner v-if="loadingDetailId" class="mr-1 inline size-3.5" />
                    {{ filteredUsers.length }} de {{ users.length }}
                    {{ users.length === 1 ? 'usuario' : 'usuarios' }}
                    <template v-if="filterRole"> · {{ roleLabel(filterRole, roles) }}</template>
                </span>
            </div>
        </template>

        <UsersTable
            :users="filteredUsers"
            :areas="areas"
            :roles="roles"
            :loading="loading"
            :has-filters="hasFilters"
            :adjusting-id="adjustingId"
            @view="openView"
            @edit="openEdit"
            @delete="deleteUser"
            @adjust="adjustDays"
            @create="openCreate"
            @clear-filters="clearFilters"
        />
    </SectionCard>

    <UserFormDialog
        v-model:open="showForm"
        :mode="formMode"
        :user="selectedUser"
        :areas="areas"
        :roles="roles"
        @saved="onSaved"
        @edit="openEdit"
    />

    <UserImportDialog v-model:open="showImport" @imported="loadUsers" />

    <RolesDialog v-if="isSuperAdmin" v-model:open="showRoles" @changed="(r) => (roles = r)" />
</template>
