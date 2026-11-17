<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Check,
    Minus,
    Pencil,
    Plus,
    Tags,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import EmptyState from '@/components/app/EmptyState.vue';
import FormField from '@/components/app/FormField.vue';
import PageContainer from '@/components/app/PageContainer.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import SectionCard from '@/components/app/SectionCard.vue';
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
import { useConfirm } from '@/composables/useConfirm';
import { useNotification } from '@/composables/useNotification';
import AppLayout from '@/layouts/AppLayout.vue';

interface AbsenceTypeItem {
    id: number;
    name: string;
    tenant_id: number | null;
    counts_as_hours: boolean;
    deducts_vacation: boolean;
    requires_approval?: boolean;
    color?: string | null;
    max_days_limit?: number | null;
}

interface Duplicate {
    name: string;
    tenant_count: number;
    global_count: number;
    absences_count: number;
}

const props = defineProps<{
    types: AbsenceTypeItem[];
    globalTypes: AbsenceTypeItem[];
    duplicates: Duplicate[];
}>();

const notify = useNotification();
const confirm = useConfirm();

const showForm = ref(false);
const editing = ref<AbsenceTypeItem | null>(null);
const deletingId = ref<number | null>(null);

const form = useForm({
    name: '',
    color: '#4F5BD5',
    counts_as_hours: false,
    deducts_vacation: false,
    requires_approval: true,
    max_days_limit: null as number | null,
});

const allTypes = computed(() => [
    ...(props.types ?? []).map((t) => ({ ...t, local: true })),
    ...(props.globalTypes ?? []).map((t) => ({ ...t, local: false })),
]);

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showForm.value = true;
};

const openEdit = (type: AbsenceTypeItem) => {
    editing.value = type;
    form.clearErrors();
    form.name = type.name;
    form.color = type.color || '#4F5BD5';
    form.counts_as_hours = !!type.counts_as_hours;
    form.deducts_vacation = !!type.deducts_vacation;
    form.requires_approval = type.requires_approval ?? true;
    form.max_days_limit = type.max_days_limit ?? null;
    showForm.value = true;
};

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            notify.success(editing.value ? 'Tipo actualizado' : 'Tipo creado');
            showForm.value = false;
            form.reset();
        },
        onError: () => notify.error('Revisa los campos marcados.'),
    };
    if (editing.value) {
        form.put(`/admin/absence-types/${editing.value.id}`, options);
    } else {
        form.post('/admin/absence-types', options);
    }
};

const deleteType = async (type: AbsenceTypeItem) => {
    const ok = await confirm({
        title: 'Eliminar tipo de ausencia',
        description: `¿Eliminar «${type.name}»? Si ya tiene ausencias registradas no podrá eliminarse.`,
        confirmText: 'Eliminar',
        variant: 'destructive',
    });
    if (!ok) return;
    deletingId.value = type.id;
    router.delete(`/admin/absence-types/${type.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            // El backend redirige "back" también cuando no puede eliminar; se verifica en los props.
            if ((props.types ?? []).some((t) => t.id === type.id)) {
                notify.error(
                    'No se puede eliminar un tipo con ausencias registradas.',
                );
            } else {
                notify.success('Tipo eliminado');
            }
        },
        onError: () => notify.error('No se pudo eliminar el tipo.'),
        onFinish: () => (deletingId.value = null),
    });
};
</script>

<template>
    <Head title="Tipos de ausencia" />
    <AppLayout>
        <PageContainer>
            <PageHeader
                title="Tipos de ausencia"
                description="Define las categorías de ausencias y novedades que pueden solicitar los colaboradores."
                :icon="Tags"
            >
                <template #actions>
                    <Button @click="openCreate"><Plus /> Nuevo tipo</Button>
                </template>
            </PageHeader>

            <div
                v-if="duplicates?.length"
                class="flex gap-3 rounded-xl border border-warning/25 bg-warning-soft p-4 text-sm"
                role="alert"
            >
                <AlertTriangle
                    class="mt-0.5 size-4 shrink-0 text-warning"
                    aria-hidden="true"
                />
                <div class="space-y-1">
                    <p class="font-medium text-warning">
                        Hay ausencias que aún usan tipos globales duplicados
                    </p>
                    <p class="text-foreground/80">
                        Existen con el mismo nombre en tu empresa y en el
                        sistema. Unifícalos para que reportes y saldos usen una
                        sola regla:
                    </p>
                    <ul class="list-disc pl-4 text-foreground/80">
                        <li v-for="dup in duplicates" :key="dup.name">
                            <strong>{{ dup.name }}</strong
                            >: {{ dup.absences_count }}
                            {{
                                dup.absences_count === 1
                                    ? 'ausencia usa'
                                    : 'ausencias usan'
                            }}
                            el tipo global
                        </li>
                    </ul>
                </div>
            </div>

            <SectionCard
                flush
                title="Todos los tipos"
                :description="`${types?.length ?? 0} propios de la empresa · ${globalTypes?.length ?? 0} globales del sistema`"
            >
                <EmptyState
                    v-if="!allTypes.length"
                    :icon="Tags"
                    title="Aún no hay tipos de ausencia"
                    description="Crea el primero para que tu equipo pueda registrar ausencias."
                >
                    <Button size="sm" @click="openCreate"
                        ><Plus /> Nuevo tipo</Button
                    >
                </EmptyState>
                <div v-else class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Nombre</th>
                                <th scope="col">Origen</th>
                                <th scope="col" class="text-center">
                                    Cuenta en horas
                                </th>
                                <th scope="col" class="text-center">
                                    Descuenta vacaciones
                                </th>
                                <th scope="col" class="text-center">
                                    Requiere aprobación
                                </th>
                                <th scope="col" class="text-right">
                                    <span class="sr-only">Acciones</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="type in allTypes" :key="type.id">
                                <td>
                                    <span
                                        class="inline-flex items-center gap-2 font-medium"
                                    >
                                        <span
                                            class="size-2.5 shrink-0 rounded-full bg-muted-foreground"
                                            :style="
                                                type.color
                                                    ? {
                                                          backgroundColor:
                                                              type.color,
                                                      }
                                                    : undefined
                                            "
                                            aria-hidden="true"
                                        />
                                        {{ type.name }}
                                    </span>
                                </td>
                                <td>
                                    <StatusBadge
                                        :tone="
                                            type.local ? 'primary' : 'neutral'
                                        "
                                        :label="
                                            type.local ? 'Empresa' : 'Global'
                                        "
                                        :dot="false"
                                    />
                                </td>
                                <td
                                    v-for="flag in [
                                        'counts_as_hours',
                                        'deducts_vacation',
                                        'requires_approval',
                                    ] as const"
                                    :key="flag"
                                    class="text-center"
                                >
                                    <Check
                                        v-if="type[flag]"
                                        class="mx-auto size-4 text-success"
                                        aria-label="Sí"
                                    />
                                    <Minus
                                        v-else
                                        class="mx-auto size-4 text-muted-foreground/60"
                                        aria-label="No"
                                    />
                                </td>
                                <td class="text-right">
                                    <div
                                        v-if="type.local"
                                        class="flex justify-end gap-0.5"
                                    >
                                        <Button
                                            variant="ghost"
                                            size="icon-sm"
                                            :aria-label="`Editar ${type.name}`"
                                            @click="openEdit(type)"
                                        >
                                            <Pencil />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon-sm"
                                            class="text-destructive hover:bg-destructive-soft hover:text-destructive"
                                            :disabled="deletingId === type.id"
                                            :aria-label="`Eliminar ${type.name}`"
                                            @click="deleteType(type)"
                                        >
                                            <Spinner
                                                v-if="deletingId === type.id"
                                            />
                                            <Trash2 v-else />
                                        </Button>
                                    </div>
                                    <span
                                        v-else
                                        class="text-xs text-muted-foreground"
                                        >Del sistema</span
                                    >
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </SectionCard>
        </PageContainer>

        <Dialog v-model:open="showForm">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{
                        editing
                            ? 'Editar tipo de ausencia'
                            : 'Nuevo tipo de ausencia'
                    }}</DialogTitle>
                    <DialogDescription>
                        Estos tipos solo están disponibles para tu empresa.
                    </DialogDescription>
                </DialogHeader>
                <form
                    id="absence-type-form"
                    class="grid gap-4"
                    @submit.prevent="submit"
                >
                    <FormField
                        label="Nombre"
                        for="at-name"
                        required
                        :error="form.errors.name"
                    >
                        <Input
                            id="at-name"
                            v-model="form.name"
                            required
                            placeholder="Ej.: Licencia de maternidad"
                            :aria-invalid="!!form.errors.name"
                        />
                    </FormField>
                    <div class="grid grid-cols-2 gap-4">
                        <FormField
                            label="Color"
                            for="at-color"
                            :error="form.errors.color"
                        >
                            <div class="flex items-center gap-2">
                                <input
                                    id="at-color"
                                    v-model="form.color"
                                    type="color"
                                    class="h-9 w-12 cursor-pointer rounded-md border bg-card p-1"
                                />
                                <span
                                    class="text-sm text-muted-foreground uppercase tabular-nums"
                                    >{{ form.color }}</span
                                >
                            </div>
                        </FormField>
                        <FormField
                            label="Máximo de días"
                            for="at-max"
                            :error="form.errors.max_days_limit"
                            hint="Vacío = sin límite"
                        >
                            <Input
                                id="at-max"
                                v-model.number="form.max_days_limit"
                                type="number"
                                min="1"
                            />
                        </FormField>
                    </div>
                    <div class="grid gap-3 rounded-lg border p-3">
                        <div class="flex items-start gap-2.5">
                            <Checkbox
                                id="at-hours"
                                v-model="form.counts_as_hours"
                                class="mt-0.5"
                            />
                            <Label
                                for="at-hours"
                                class="grid gap-0.5 font-normal"
                            >
                                Cuenta en horas
                                <span class="text-xs text-muted-foreground"
                                    >Se registra por horas en lugar de
                                    días.</span
                                >
                            </Label>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <Checkbox
                                id="at-vac"
                                v-model="form.deducts_vacation"
                                class="mt-0.5"
                            />
                            <Label
                                for="at-vac"
                                class="grid gap-0.5 font-normal"
                            >
                                Descuenta vacaciones
                                <span class="text-xs text-muted-foreground"
                                    >Resta del saldo de vacaciones del
                                    colaborador.</span
                                >
                            </Label>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <Checkbox
                                id="at-approval"
                                v-model="form.requires_approval"
                                class="mt-0.5"
                            />
                            <Label
                                for="at-approval"
                                class="grid gap-0.5 font-normal"
                            >
                                Requiere aprobación
                                <span class="text-xs text-muted-foreground"
                                    >Queda pendiente hasta que un líder la
                                    apruebe.</span
                                >
                            </Label>
                        </div>
                    </div>
                </form>
                <DialogFooter>
                    <Button
                        variant="outline"
                        :disabled="form.processing"
                        @click="showForm = false"
                        >Cancelar</Button
                    >
                    <Button
                        type="submit"
                        form="absence-type-form"
                        :disabled="form.processing"
                    >
                        <Spinner v-if="form.processing" />
                        {{
                            form.processing
                                ? 'Guardando…'
                                : editing
                                  ? 'Guardar cambios'
                                  : 'Crear tipo'
                        }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
