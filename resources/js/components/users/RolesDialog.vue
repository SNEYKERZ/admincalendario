<script setup lang="ts">
import axios from 'axios';
import { Pencil, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import FormField from '@/components/app/FormField.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { useConfirm } from '@/composables/useConfirm';
import { useNotification } from '@/composables/useNotification';
import { extractValidationErrors } from './types';
import type { UserRole, ValidationErrors } from './types';

const props = defineProps<{ open: boolean }>();
const emit = defineEmits<{
    'update:open': [value: boolean];
    changed: [roles: UserRole[]];
}>();

const notify = useNotification();
const confirm = useConfirm();

const roles = ref<UserRole[]>([]);
const loading = ref(false);
const saving = ref(false);
const editingRole = ref<UserRole | null>(null);
const errors = ref<ValidationErrors>({});

const emptyForm = () => ({
    name: '',
    display_name: '',
    description: '',
    color: '#6B7280',
    is_active: true,
    display_order: 0,
});
const roleForm = ref(emptyForm());

const loadRoles = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/admin/roles');
        roles.value = res.data;
        emit('changed', roles.value);
    } catch {
        notify.error('No se pudieron cargar los roles.');
    } finally {
        loading.value = false;
    }
};

watch(
    () => props.open,
    (open) => {
        if (open) {
            resetForm();
            loadRoles();
        }
    },
);

const resetForm = () => {
    editingRole.value = null;
    roleForm.value = emptyForm();
    errors.value = {};
};

const editRole = (role: UserRole) => {
    editingRole.value = role;
    errors.value = {};
    roleForm.value = {
        name: role.name,
        display_name: role.display_name,
        description: role.description || '',
        color: role.color,
        is_active: role.is_active,
        display_order: role.display_order,
    };
};

const saveRole = async () => {
    saving.value = true;
    errors.value = {};
    try {
        if (editingRole.value) {
            await axios.put(`/admin/roles/${editingRole.value.id}`, roleForm.value);
            notify.success('Rol actualizado');
        } else {
            await axios.post('/admin/roles', roleForm.value);
            notify.success('Rol creado');
        }
        resetForm();
        await loadRoles();
    } catch (e: any) {
        errors.value = extractValidationErrors(e);
        notify.error(e?.response?.data?.message || 'No se pudo guardar el rol.');
    } finally {
        saving.value = false;
    }
};

const deleteRole = async (role: UserRole) => {
    const ok = await confirm({
        title: 'Eliminar rol',
        description: `¿Eliminar el rol «${role.display_name}»? Esta acción no se puede deshacer.`,
        variant: 'destructive',
    });
    if (!ok) return;
    try {
        await axios.delete(`/admin/roles/${role.id}`);
        notify.success('Rol eliminado');
        await loadRoles();
    } catch (e: any) {
        notify.error(e?.response?.data?.message || 'No se pudo eliminar el rol.');
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="(v: boolean) => emit('update:open', v)">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Gestión de roles</DialogTitle>
                <DialogDescription>
                    Crea y edita los roles disponibles. Los roles del sistema no se pueden modificar.
                </DialogDescription>
            </DialogHeader>

            <form class="rounded-lg border bg-muted/40 p-4" @submit.prevent="saveRole">
                <h3 class="mb-3 text-sm font-semibold">
                    {{ editingRole ? `Editar «${editingRole.display_name}»` : 'Nuevo rol' }}
                </h3>
                <div class="grid gap-3 sm:grid-cols-2">
                    <FormField label="Identificador (slug)" for="role-name" required :error="errors.name">
                        <Input
                            id="role-name"
                            v-model="roleForm.name"
                            placeholder="ej. supervisor"
                            :disabled="!!editingRole"
                        />
                    </FormField>
                    <FormField label="Nombre visible" for="role-display" required :error="errors.display_name">
                        <Input id="role-display" v-model="roleForm.display_name" placeholder="ej. Supervisor" />
                    </FormField>
                    <FormField label="Descripción" for="role-desc" class="sm:col-span-2" :error="errors.description">
                        <Input id="role-desc" v-model="roleForm.description" placeholder="Para qué sirve este rol" />
                    </FormField>
                    <FormField label="Color" for="role-color" :error="errors.color">
                        <div class="flex items-center gap-2">
                            <input
                                id="role-color"
                                v-model="roleForm.color"
                                type="color"
                                class="h-9 w-12 cursor-pointer rounded-md border bg-card p-1"
                            />
                            <span class="text-sm text-muted-foreground tabular-nums">{{ roleForm.color }}</span>
                        </div>
                    </FormField>
                    <FormField label="Orden" for="role-order" :error="errors.display_order">
                        <Input id="role-order" v-model.number="roleForm.display_order" type="number" min="0" />
                    </FormField>
                    <div class="flex items-center gap-2 sm:col-span-2">
                        <Checkbox id="role-active" v-model="roleForm.is_active" />
                        <Label for="role-active" class="font-normal">Rol activo</Label>
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    <Button type="submit" size="sm" :disabled="saving">
                        <Spinner v-if="saving" />
                        {{ editingRole ? 'Guardar cambios' : 'Crear rol' }}
                    </Button>
                    <Button v-if="editingRole" type="button" variant="outline" size="sm" @click="resetForm">
                        Cancelar edición
                    </Button>
                </div>
            </form>

            <div>
                <h3 class="mb-2 text-sm font-semibold">Roles existentes</h3>
                <div v-if="loading" class="space-y-2">
                    <Skeleton v-for="n in 3" :key="n" class="h-12 w-full" />
                </div>
                <p v-else-if="!roles.length" class="py-6 text-center text-sm text-muted-foreground">
                    No hay roles configurados.
                </p>
                <ul v-else class="divide-y rounded-lg border">
                    <li v-for="role in roles" :key="role.id" class="flex items-center justify-between gap-3 px-3 py-2.5">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="size-3 shrink-0 rounded-full" :style="{ backgroundColor: role.color }" aria-hidden="true" />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ role.display_name }}</p>
                                <p class="truncate text-xs text-muted-foreground">
                                    {{ role.name }} · {{ role.user_count }} usuarios
                                    <span v-if="role.is_system"> · Sistema</span>
                                </p>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            <StatusBadge :status="role.is_active ? 'activo' : 'inactivo'" />
                            <Button
                                v-if="!role.is_system"
                                variant="ghost"
                                size="icon-sm"
                                :aria-label="`Editar rol ${role.display_name}`"
                                @click="editRole(role)"
                            >
                                <Pencil />
                            </Button>
                            <Button
                                v-if="!role.is_system && role.user_count === 0"
                                variant="ghost"
                                size="icon-sm"
                                class="text-destructive hover:bg-destructive-soft hover:text-destructive"
                                :aria-label="`Eliminar rol ${role.display_name}`"
                                @click="deleteRole(role)"
                            >
                                <Trash2 />
                            </Button>
                        </div>
                    </li>
                </ul>
            </div>
        </DialogContent>
    </Dialog>
</template>
