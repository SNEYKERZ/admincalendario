<script setup lang="ts">
import axios from 'axios';
import { Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import FormField from '@/components/app/FormField.vue';
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
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useNotification } from '@/composables/useNotification';
import { includesNormalized } from '@/lib/search';
import type { AreaItem } from './types';

interface LeaderCandidate {
    id: number;
    name: string;
    email: string;
}

const props = defineProps<{
    open: boolean;
    area: AreaItem | null;
    currentUserId: number | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    saved: [];
}>();

const notify = useNotification();

const emptyForm = () => ({
    name: '',
    description: '',
    color: '#4F5BD5',
    display_order: 0,
    is_active: true,
    leader_ids: [] as number[],
});

const form = ref(emptyForm());
const errors = ref<Record<string, string>>({});
const users = ref<LeaderCandidate[]>([]);
const initialLeaderIds = ref<number[]>([]);
const loadingData = ref(false);
const saving = ref(false);
const leaderSearch = ref('');

const isEdit = computed(() => !!props.area);

const filteredCandidates = computed(() =>
    users.value.filter(
        (u) => includesNormalized(u.name, leaderSearch.value) || includesNormalized(u.email, leaderSearch.value),
    ),
);

const isLockedSelf = (id: number) =>
    isEdit.value && id === props.currentUserId && initialLeaderIds.value.includes(id);

const toggleLeader = (id: number, checked: boolean | 'indeterminate') => {
    const set = new Set(form.value.leader_ids);
    if (checked === true) set.add(id);
    else set.delete(id);
    form.value.leader_ids = [...set];
};

const loadUsers = async () => {
    try {
        const res = await axios.get('/api/users', { params: { role: 'admin,employee' } });
        users.value = res.data.users || [];
    } catch (e) {
        console.error('Error loading users:', e);
    }
};

watch(
    () => props.open,
    async (open) => {
        if (!open) return;
        errors.value = {};
        leaderSearch.value = '';
        form.value = emptyForm();
        initialLeaderIds.value = [];
        loadingData.value = true;
        try {
            const tasks: Promise<unknown>[] = [loadUsers()];
            if (props.area) {
                tasks.push(
                    axios
                        .get(`/api/areas/${props.area.id}`)
                        .then((res) => {
                            const a = res.data.area;
                            form.value = {
                                name: a.name,
                                description: a.description || '',
                                color: a.color,
                                display_order: a.display_order,
                                is_active: a.is_active,
                                leader_ids: a.leader_ids || [],
                            };
                            initialLeaderIds.value = a.leader_ids || [];
                        })
                        .catch((e) => {
                            console.error('Error loading area:', e);
                            notify.error('No se pudo cargar el área.');
                        }),
                );
            }
            await Promise.all(tasks);
        } finally {
            loadingData.value = false;
        }
    },
);

const save = async () => {
    saving.value = true;
    errors.value = {};
    try {
        if (props.area) {
            await axios.put(`/api/areas/${props.area.id}`, form.value);
            notify.success('Área actualizada');
        } else {
            await axios.post('/api/areas', form.value);
            notify.success('Área creada');
        }
        emit('saved');
        emit('update:open', false);
    } catch (e: any) {
        const raw = e?.response?.data?.errors;
        if (raw && typeof raw === 'object') {
            errors.value = Object.fromEntries(
                Object.entries(raw).map(([k, v]) => [k, Array.isArray(v) ? String(v[0]) : String(v)]),
            );
        }
        notify.error(e?.response?.data?.message || 'No se pudo guardar el área.');
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="(v: boolean) => emit('update:open', v)">
        <DialogContent class="sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>{{ isEdit ? 'Editar área' : 'Nueva área' }}</DialogTitle>
                <DialogDescription>
                    Las áreas agrupan a los colaboradores. Sus líderes aprueban las ausencias del área.
                </DialogDescription>
            </DialogHeader>

            <form id="area-form" class="grid gap-4" @submit.prevent="save">
                <FormField label="Nombre" for="area-name" required :error="errors.name">
                    <Input
                        id="area-name"
                        v-model="form.name"
                        required
                        placeholder="Ej.: Desarrollo, Marketing, Ventas"
                        :aria-invalid="!!errors.name"
                        :disabled="loadingData"
                    />
                </FormField>

                <FormField label="Descripción" for="area-desc" :error="errors.description">
                    <Textarea
                        id="area-desc"
                        v-model="form.description"
                        rows="2"
                        placeholder="Descripción opcional del área"
                        :disabled="loadingData"
                    />
                </FormField>

                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Color" for="area-color" :error="errors.color">
                        <div class="flex items-center gap-2">
                            <input
                                id="area-color"
                                v-model="form.color"
                                type="color"
                                class="h-9 w-12 cursor-pointer rounded-md border bg-card p-1"
                            />
                            <span class="text-sm text-muted-foreground uppercase tabular-nums">{{ form.color }}</span>
                        </div>
                    </FormField>
                    <FormField label="Orden" for="area-order" :error="errors.display_order">
                        <Input id="area-order" v-model.number="form.display_order" type="number" min="0" />
                    </FormField>
                </div>

                <div class="flex items-center gap-2">
                    <Checkbox id="area-active" v-model="form.is_active" />
                    <Label for="area-active" class="font-normal">Área activa</Label>
                </div>

                <fieldset class="grid gap-2">
                    <legend class="mb-1.5 text-[13px] font-medium">
                        Líderes del área
                        <span class="font-normal text-muted-foreground">
                            (opcional · {{ form.leader_ids.length }} seleccionados)
                        </span>
                    </legend>
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <Input
                            v-model="leaderSearch"
                            type="search"
                            class="pl-9"
                            placeholder="Buscar persona"
                            aria-label="Buscar líderes"
                        />
                    </div>
                    <div class="max-h-56 overflow-y-auto rounded-lg border">
                        <div v-if="loadingData" class="space-y-2 p-3">
                            <Skeleton v-for="n in 4" :key="n" class="h-8 w-full" />
                        </div>
                        <p
                            v-else-if="!filteredCandidates.length"
                            class="px-3 py-6 text-center text-sm text-muted-foreground"
                        >
                            No hay personas que coincidan.
                        </p>
                        <div
                            v-for="user in loadingData ? [] : filteredCandidates"
                            :key="user.id"
                            class="flex items-start gap-2.5 border-b px-3 py-2 last:border-b-0 hover:bg-muted/40"
                        >
                            <Checkbox
                                :id="`leader_${user.id}`"
                                class="mt-0.5"
                                :model-value="form.leader_ids.includes(user.id)"
                                :disabled="isLockedSelf(user.id)"
                                @update:model-value="(v) => toggleLeader(user.id, v)"
                            />
                            <label :for="`leader_${user.id}`" class="min-w-0 flex-1 cursor-pointer text-sm">
                                <span class="font-medium">{{ user.name }}</span>
                                <span class="ml-1.5 text-xs text-muted-foreground">{{ user.email }}</span>
                                <span v-if="isLockedSelf(user.id)" class="mt-0.5 block text-xs text-warning">
                                    No puedes quitarte como líder. Pídele a otro administrador que lo haga.
                                </span>
                            </label>
                        </div>
                    </div>
                    <p v-if="errors.leader_ids" class="text-xs text-destructive" role="alert">
                        {{ errors.leader_ids }}
                    </p>
                </fieldset>
            </form>

            <DialogFooter>
                <Button variant="outline" :disabled="saving" @click="emit('update:open', false)">Cancelar</Button>
                <Button type="submit" form="area-form" :disabled="saving || loadingData">
                    <Spinner v-if="saving" />
                    {{ saving ? 'Guardando…' : isEdit ? 'Guardar cambios' : 'Crear área' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
