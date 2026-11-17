<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { Check, ClipboardCheck, RefreshCw, X } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import EmptyState from '@/components/app/EmptyState.vue';
import FormField from '@/components/app/FormField.vue';
import PageContainer from '@/components/app/PageContainer.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useNotification } from '@/composables/useNotification';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatAbsenceDuration } from '@/lib/format';

type ApprovalChain = {
    id: number;
    absence: {
        id: number;
        start_datetime: string;
        end_datetime: string;
        total_days?: number | string | null;
        total_hours?: number | string | null;
        internal_notes?: string | null;
        user?: { name: string; area?: { name: string } | null } | null;
        type?: { name: string; color?: string | null } | null;
    };
};

const notify = useNotification();

const chains = ref<ApprovalChain[]>([]);
const loading = ref(true);
const selected = ref<ApprovalChain | null>(null);
const mode = ref<'approve' | 'reject' | null>(null);
const notes = ref('');
const reasonPreset = ref('');
const rejectReason = ref('');
const submitting = ref(false);
const fieldError = ref<string | null>(null);

const REASONS: Record<string, string> = {
    insufficient_balance: 'No tienes saldo disponible para este período',
    blackout_period: 'El período solicitado está en bloqueo operacional',
    operational_need:
        'Se requiere tu presencia en operaciones durante este período',
};

const dialogOpen = computed({
    get: () => mode.value !== null,
    set: (v: boolean) => {
        if (!v) mode.value = null;
    },
});

const fetchPending = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get('/approvals/pending');
        chains.value = Array.isArray(data) ? data : (data?.data ?? []);
    } catch {
        notify.error('No se pudieron cargar las aprobaciones pendientes.');
    } finally {
        loading.value = false;
    }
};

const open = (chain: ApprovalChain, m: 'approve' | 'reject') => {
    selected.value = chain;
    mode.value = m;
    notes.value = '';
    reasonPreset.value = '';
    rejectReason.value = '';
    fieldError.value = null;
};

const applyPreset = () => {
    rejectReason.value = REASONS[reasonPreset.value] ?? rejectReason.value;
};

const submit = async () => {
    if (!selected.value || !mode.value) return;
    if (mode.value === 'reject' && !rejectReason.value.trim()) {
        fieldError.value = 'Indica el motivo del rechazo.';
        return;
    }
    submitting.value = true;
    fieldError.value = null;
    try {
        if (mode.value === 'approve') {
            await axios.post(`/approvals/${selected.value.id}/approve`, {
                notes: notes.value || null,
            });
            notify.success('Ausencia aprobada.');
        } else {
            await axios.post(`/approvals/${selected.value.id}/reject`, {
                reason: rejectReason.value,
            });
            notify.success('Ausencia rechazada.');
        }
        mode.value = null;
        await fetchPending();
    } catch (e: any) {
        const errors = e?.response?.data?.errors;
        fieldError.value = errors?.reason?.[0] ?? errors?.notes?.[0] ?? null;
        notify.error(
            e?.response?.data?.message ??
                'No se pudo completar la acción. Inténtalo de nuevo.',
        );
    } finally {
        submitting.value = false;
    }
};

const formatDate = (date?: string | null) =>
    date
        ? new Date(date).toLocaleDateString('es-CO', {
              day: '2-digit',
              month: 'short',
              year: 'numeric',
          })
        : '—';

onMounted(fetchPending);
</script>

<template>
    <Head title="Aprobaciones pendientes" />
    <AppLayout>
        <PageContainer>
            <PageHeader
                title="Aprobaciones pendientes"
                description="Ausencias y novedades asignadas a ti para aprobar o rechazar."
                :icon="ClipboardCheck"
            >
                <template #actions>
                    <Button
                        variant="outline"
                        :disabled="loading"
                        @click="fetchPending"
                    >
                        <RefreshCw :class="loading ? 'animate-spin' : ''" />
                        Actualizar
                    </Button>
                </template>
            </PageHeader>

            <div v-if="loading" class="grid gap-3">
                <Skeleton v-for="i in 3" :key="i" class="h-28 rounded-xl" />
            </div>

            <div v-else-if="!chains.length" class="rounded-xl border bg-card">
                <EmptyState
                    :icon="ClipboardCheck"
                    title="No tienes aprobaciones pendientes"
                    description="Cuando un colaborador solicite una ausencia que debas revisar, aparecerá aquí."
                />
            </div>

            <ul v-else class="grid gap-3">
                <li
                    v-for="chain in chains"
                    :key="chain.id"
                    class="flex flex-col gap-4 rounded-xl border bg-card p-4 sm:flex-row sm:items-start sm:justify-between sm:p-5"
                >
                    <div class="min-w-0 flex-1 space-y-3">
                        <div class="flex items-center gap-2">
                            <span
                                class="size-2.5 shrink-0 rounded-full"
                                :style="{
                                    backgroundColor:
                                        chain.absence.type?.color ||
                                        'var(--primary)',
                                }"
                                aria-hidden="true"
                            />
                            <h2 class="truncate font-semibold">
                                {{ chain.absence.user?.name ?? 'Colaborador' }}
                            </h2>
                            <span
                                v-if="chain.absence.user?.area?.name"
                                class="truncate text-sm text-muted-foreground"
                                >· {{ chain.absence.user.area.name }}</span
                            >
                        </div>
                        <dl
                            class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm sm:grid-cols-3"
                        >
                            <div>
                                <dt class="text-xs text-muted-foreground">
                                    Tipo
                                </dt>
                                <dd class="font-medium">
                                    {{ chain.absence.type?.name ?? '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted-foreground">
                                    Duración
                                </dt>
                                <dd class="font-medium tabular-nums">
                                    {{
                                        formatAbsenceDuration(
                                            chain.absence.total_days,
                                            chain.absence.total_hours,
                                        )
                                    }}
                                </dd>
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <dt class="text-xs text-muted-foreground">
                                    Período
                                </dt>
                                <dd class="font-medium">
                                    {{
                                        formatDate(chain.absence.start_datetime)
                                    }}
                                    –
                                    {{ formatDate(chain.absence.end_datetime) }}
                                </dd>
                            </div>
                        </dl>
                        <p
                            v-if="chain.absence.internal_notes"
                            class="rounded-md bg-muted px-3 py-2 text-sm text-muted-foreground"
                        >
                            {{ chain.absence.internal_notes }}
                        </p>
                    </div>
                    <div class="flex gap-2 sm:shrink-0">
                        <Button
                            variant="outline"
                            class="flex-1 sm:flex-none"
                            @click="open(chain, 'reject')"
                        >
                            <X /> Rechazar
                        </Button>
                        <Button
                            class="flex-1 sm:flex-none"
                            @click="open(chain, 'approve')"
                        >
                            <Check /> Aprobar
                        </Button>
                    </div>
                </li>
            </ul>
        </PageContainer>

        <Dialog v-model:open="dialogOpen">
            <DialogContent v-if="selected" class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {{
                            mode === 'approve'
                                ? 'Aprobar ausencia'
                                : 'Rechazar ausencia'
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ selected.absence.user?.name }} ·
                        {{ formatDate(selected.absence.start_datetime) }} –
                        {{ formatDate(selected.absence.end_datetime) }}
                    </DialogDescription>
                </DialogHeader>

                <FormField
                    v-if="mode === 'approve'"
                    label="Notas (opcional)"
                    for="approve-notes"
                    :error="fieldError"
                >
                    <Textarea
                        id="approve-notes"
                        v-model="notes"
                        maxlength="500"
                        placeholder="Comentario para el colaborador"
                    />
                </FormField>

                <template v-else>
                    <FormField label="Motivo frecuente" for="reject-preset">
                        <select
                            id="reject-preset"
                            v-model="reasonPreset"
                            class="input"
                            @change="applyPreset"
                        >
                            <option value="">Seleccionar…</option>
                            <option value="insufficient_balance">
                                Saldo insuficiente
                            </option>
                            <option value="blackout_period">
                                Período de bloqueo
                            </option>
                            <option value="operational_need">
                                Necesidad operacional
                            </option>
                        </select>
                    </FormField>
                    <FormField
                        label="Motivo del rechazo"
                        for="reject-reason"
                        required
                        :error="fieldError"
                    >
                        <Textarea
                            id="reject-reason"
                            v-model="rejectReason"
                            :aria-invalid="!!fieldError"
                            placeholder="Explica al colaborador por qué se rechaza"
                        />
                    </FormField>
                </template>

                <DialogFooter>
                    <Button
                        variant="outline"
                        :disabled="submitting"
                        @click="mode = null"
                    >
                        Cancelar
                    </Button>
                    <Button
                        :variant="mode === 'reject' ? 'destructive' : 'default'"
                        :disabled="submitting"
                        @click="submit"
                    >
                        <Spinner v-if="submitting" />
                        {{ mode === 'approve' ? 'Aprobar' : 'Rechazar' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
