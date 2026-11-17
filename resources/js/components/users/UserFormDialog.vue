<script setup lang="ts">
import axios from 'axios';
import { computed, ref, watch } from 'vue';
import FormField from '@/components/app/FormField.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useNotification } from '@/composables/useNotification';
import { extractValidationErrors, formatDate, getInitials, roleLabel } from './types';
import type {
    ManagedUser,
    UserArea,
    UserFormMode,
    UserRole,
    ValidationErrors,
} from './types';

const props = defineProps<{
    open: boolean;
    mode: UserFormMode;
    user: ManagedUser | null;
    areas: UserArea[];
    roles: UserRole[];
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    saved: [];
    edit: [user: ManagedUser];
}>();

const notify = useNotification();

const emptyForm = () => ({
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
    photo: null as File | null,
    area_id: null as number | null,
    is_active: true,
});

const form = ref(emptyForm());
const errors = ref<ValidationErrors>({});
const saving = ref(false);
const vacationDays = ref<number>(0);
const savingVacation = ref(false);
const photoInput = ref<HTMLInputElement | null>(null);

const isView = computed(() => props.mode === 'view');

const title = computed(() =>
    props.mode === 'create'
        ? 'Nuevo usuario'
        : props.mode === 'edit'
          ? 'Editar usuario'
          : 'Detalle del usuario',
);

const description = computed(() =>
    props.mode === 'create'
        ? 'Completa los datos del colaborador. Los campos con * son obligatorios.'
        : props.mode === 'edit'
          ? 'Actualiza la información del colaborador.'
          : 'Información personal y saldo de vacaciones del año en curso.',
);

watch(
    () => [props.open, props.user, props.mode] as const,
    ([open]) => {
        if (!open) return;
        errors.value = {};
        const u = props.user;
        if (!u || props.mode === 'create') {
            form.value = emptyForm();
            return;
        }
        form.value = {
            first_name: u.first_name || '',
            last_name: u.last_name || '',
            identification: u.identification || '',
            gender: u.gender || '',
            phone: u.phone || '',
            email: u.email,
            password: '',
            role: u.role,
            birth_date: u.birth_date ? u.birth_date.slice(0, 10) : '',
            hire_date: u.hire_date ? u.hire_date.slice(0, 10) : '',
            photo: null,
            area_id: u.area_id || null,
            is_active: u.is_active ?? true,
        };
        vacationDays.value = u.allocated ?? 0;
        if (photoInput.value) photoInput.value.value = '';
    },
    { immediate: true },
);

const roleOptions = computed(() =>
    props.roles.length > 0
        ? props.roles.map((r) => ({ value: r.name, label: r.display_name }))
        : [
              { value: 'colaborador', label: 'Colaborador' },
              { value: 'admin', label: 'Administrador' },
          ],
);

const onPhotoChange = (e: Event) => {
    form.value.photo = (e.target as HTMLInputElement).files?.[0] || null;
};

const close = () => emit('update:open', false);

const save = async () => {
    saving.value = true;
    errors.value = {};
    try {
        const formData = new FormData();
        Object.entries(form.value).forEach(([key, value]) => {
            if (key === 'photo') {
                if (value) formData.append('photo', value as File);
            } else if (key === 'is_active') {
                // En creación el backend no valida is_active: sólo se envía al editar.
                if (props.mode === 'edit') formData.append('is_active', value ? '1' : '0');
            } else if (value !== null && value !== '') {
                formData.append(key, String(value));
            }
        });

        if (props.mode === 'create') {
            await axios.post('/admin/users', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            notify.success('Usuario creado');
        } else if (props.user) {
            // PHP no interpreta multipart en PUT: se usa POST + _method (spoofing de Laravel).
            formData.append('_method', 'PUT');
            await axios.post(`/admin/users/${props.user.id}`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            notify.success('Usuario actualizado');
        }

        emit('saved');
        close();
    } catch (e: any) {
        errors.value = extractValidationErrors(e);
        if (Object.keys(errors.value).length) {
            notify.error('Revisa los campos marcados.');
        } else {
            notify.error(e?.response?.data?.message || 'No se pudo guardar el usuario.');
        }
    } finally {
        saving.value = false;
    }
};

const saveVacation = async () => {
    if (!props.user) return;
    savingVacation.value = true;
    try {
        await axios.post('/admin/vacation-years', {
            user_id: props.user.id,
            year: new Date().getFullYear(),
            allocated_days: vacationDays.value,
        });
        notify.success('Días de vacaciones actualizados');
        emit('saved');
    } catch (e: any) {
        console.error(e);
        notify.error(
            e?.response?.data?.message || 'No se pudieron actualizar los días de vacaciones.',
        );
    } finally {
        savingVacation.value = false;
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="(v: boolean) => emit('update:open', v)">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>

            <!-- Vista de detalle -->
            <div v-if="isView && user" class="space-y-5">
                <div class="flex items-center gap-3">
                    <img
                        v-if="user.photo_url"
                        :src="user.photo_url"
                        :alt="`Foto de ${user.name}`"
                        class="size-12 rounded-full object-cover"
                    />
                    <div
                        v-else
                        class="flex size-12 items-center justify-center rounded-full bg-muted font-semibold text-muted-foreground"
                        aria-hidden="true"
                    >
                        {{ getInitials(user.name) }}
                    </div>
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ user.name }}</p>
                        <p class="truncate text-sm text-muted-foreground">{{ user.email }}</p>
                    </div>
                    <div class="ml-auto flex flex-wrap justify-end gap-1.5">
                        <StatusBadge
                            :tone="user.role === 'admin' || user.role === 'superadmin' ? 'primary' : 'neutral'"
                            :label="roleLabel(user.role, roles)"
                            :dot="false"
                        />
                        <StatusBadge :status="user.is_active ? 'activo' : 'inactivo'" />
                    </div>
                </div>

                <dl class="grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-xs text-muted-foreground">Identificación</dt>
                        <dd>{{ user.identification || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">Teléfono</dt>
                        <dd>{{ user.phone || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">Área</dt>
                        <dd>{{ areas.find((a) => a.id === user?.area_id)?.name || user.area_name || 'Sin área' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">Género</dt>
                        <dd>{{ user.gender || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">Fecha de nacimiento</dt>
                        <dd>{{ formatDate(user.birth_date) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">Fecha de contratación</dt>
                        <dd>{{ formatDate(user.hire_date) }}</dd>
                    </div>
                </dl>

                <section class="rounded-lg border bg-muted/40 p-4" aria-labelledby="vac-title">
                    <h3 id="vac-title" class="text-sm font-semibold">
                        Vacaciones {{ new Date().getFullYear() }}
                    </h3>
                    <div class="mt-3 grid grid-cols-3 gap-3 text-center">
                        <div class="rounded-md border bg-card p-2">
                            <p class="text-xs text-muted-foreground">Asignados</p>
                            <p class="text-lg font-semibold tabular-nums">{{ user.allocated ?? 0 }}</p>
                        </div>
                        <div class="rounded-md border bg-card p-2">
                            <p class="text-xs text-muted-foreground">Usados</p>
                            <p class="text-lg font-semibold tabular-nums">{{ user.used ?? 0 }}</p>
                        </div>
                        <div class="rounded-md border bg-card p-2">
                            <p class="text-xs text-muted-foreground">Disponibles</p>
                            <p class="text-lg font-semibold text-success tabular-nums">{{ user.available ?? 0 }}</p>
                        </div>
                    </div>
                    <form class="mt-3 flex flex-wrap items-end gap-2" @submit.prevent="saveVacation">
                        <div class="grid gap-1.5">
                            <Label for="vacation-days" class="text-[13px]">Días asignados este año</Label>
                            <Input
                                id="vacation-days"
                                v-model.number="vacationDays"
                                type="number"
                                min="0"
                                max="60"
                                class="w-28"
                                :disabled="savingVacation"
                            />
                        </div>
                        <Button type="submit" variant="outline" :disabled="savingVacation">
                            <Spinner v-if="savingVacation" />
                            {{ savingVacation ? 'Guardando…' : 'Guardar días' }}
                        </Button>
                    </form>
                </section>
            </div>

            <!-- Formulario crear / editar -->
            <form v-else id="user-form" class="grid gap-4 sm:grid-cols-2" novalidate @submit.prevent="save">
                <FormField label="Nombre" for="uf-first-name" required :error="errors.first_name">
                    <Input id="uf-first-name" v-model="form.first_name" autocomplete="given-name" :aria-invalid="!!errors.first_name" />
                </FormField>
                <FormField label="Apellidos" for="uf-last-name" required :error="errors.last_name">
                    <Input id="uf-last-name" v-model="form.last_name" autocomplete="family-name" :aria-invalid="!!errors.last_name" />
                </FormField>
                <FormField label="Correo electrónico" for="uf-email" required :error="errors.email">
                    <Input id="uf-email" v-model="form.email" type="email" autocomplete="email" :aria-invalid="!!errors.email" />
                </FormField>
                <FormField
                    label="Contraseña"
                    for="uf-password"
                    :required="mode === 'create'"
                    :error="errors.password"
                    :hint="mode === 'edit' ? 'Déjala vacía para mantener la actual.' : 'Mínimo 8 caracteres.'"
                >
                    <Input
                        id="uf-password"
                        v-model="form.password"
                        type="password"
                        autocomplete="new-password"
                        :aria-invalid="!!errors.password"
                    />
                </FormField>
                <FormField label="Identificación" for="uf-identification" :error="errors.identification">
                    <Input id="uf-identification" v-model="form.identification" :aria-invalid="!!errors.identification" />
                </FormField>
                <FormField label="Teléfono" for="uf-phone" :error="errors.phone">
                    <Input id="uf-phone" v-model="form.phone" type="tel" autocomplete="tel" :aria-invalid="!!errors.phone" />
                </FormField>
                <FormField label="Rol" for="uf-role" required :error="errors.role">
                    <select id="uf-role" v-model="form.role" class="input" :aria-invalid="!!errors.role">
                        <option v-for="opt in roleOptions" :key="opt.value" :value="opt.value">
                            {{ opt.label }}
                        </option>
                    </select>
                </FormField>
                <FormField label="Área" for="uf-area" :error="errors.area_id">
                    <select id="uf-area" v-model="form.area_id" class="input" :aria-invalid="!!errors.area_id">
                        <option :value="null">Sin área asignada</option>
                        <option v-for="area in areas" :key="area.id" :value="area.id">
                            {{ area.name }}
                        </option>
                    </select>
                </FormField>
                <FormField label="Género" for="uf-gender" :error="errors.gender">
                    <select id="uf-gender" v-model="form.gender" class="input">
                        <option value="">Sin especificar</option>
                        <option value="Masculino">Masculino</option>
                        <option value="Femenino">Femenino</option>
                        <option value="Otro">Otro</option>
                        <option
                            v-if="form.gender && !['Masculino', 'Femenino', 'Otro'].includes(form.gender)"
                            :value="form.gender"
                        >
                            {{ form.gender }}
                        </option>
                    </select>
                </FormField>
                <FormField label="Foto" for="uf-photo" :error="errors.photo" hint="Imagen de hasta 2 MB.">
                    <input
                        id="uf-photo"
                        ref="photoInput"
                        type="file"
                        accept="image/*"
                        class="input file:mr-3 file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground"
                        @change="onPhotoChange"
                    />
                </FormField>
                <FormField label="Fecha de nacimiento" for="uf-birth" :error="errors.birth_date">
                    <Input id="uf-birth" v-model="form.birth_date" type="date" :aria-invalid="!!errors.birth_date" />
                </FormField>
                <FormField label="Fecha de contratación" for="uf-hire" :error="errors.hire_date">
                    <Input id="uf-hire" v-model="form.hire_date" type="date" :aria-invalid="!!errors.hire_date" />
                </FormField>
                <div v-if="mode === 'edit'" class="flex items-center gap-2 sm:col-span-2">
                    <Checkbox id="uf-active" v-model="form.is_active" />
                    <Label for="uf-active" class="text-sm font-normal">Usuario activo (puede iniciar sesión)</Label>
                </div>
            </form>

            <DialogFooter>
                <template v-if="isView">
                    <Button variant="outline" @click="close">Cerrar</Button>
                    <Button v-if="user" @click="emit('edit', user)">Editar usuario</Button>
                </template>
                <template v-else>
                    <Button variant="outline" :disabled="saving" @click="close">Cancelar</Button>
                    <Button type="submit" form="user-form" :disabled="saving">
                        <Spinner v-if="saving" />
                        {{ saving ? 'Guardando…' : mode === 'create' ? 'Crear usuario' : 'Guardar cambios' }}
                    </Button>
                </template>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
