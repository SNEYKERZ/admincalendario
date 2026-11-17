<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import PageContainer from '@/components/app/PageContainer.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import SectionCard from '@/components/app/SectionCard.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import RequestTimeline from '@/components/requests/RequestTimeline.vue';
import {
    formatRequestDate,
    getTypeLabel
    
} from '@/components/requests/utils';
import type {RequestApproval} from '@/components/requests/utils';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useConfirm } from '@/composables/useConfirm';
import { useNotification } from '@/composables/useNotification';
import AppLayout from '@/layouts/AppLayout.vue';

interface EmployeeRequest {
    id: number;
    request_type: string;
    status: string;
    title: string;
    description: string | null;
    rejection_reason: string | null;
    created_at: string;
    updated_at: string;
    approvals: RequestApproval[];
}

const props = defineProps<{
    request: EmployeeRequest;
}>();

const confirm = useConfirm();
const notify = useNotification();
const deleting = ref(false);

const approvals = computed(() =>
    Array.isArray(props.request.approvals) ? props.request.approvals : [],
);

const deleteRequest = async () => {
    const ok = await confirm({
        title: 'Eliminar solicitud',
        description:
            'Se eliminará esta solicitud pendiente. Esta acción no se puede deshacer.',
        variant: 'destructive',
    });
    if (!ok) return;
    router.delete(`/solicitudes/${props.request.id}`, {
        onStart: () => (deleting.value = true),
        onFinish: () => (deleting.value = false),
        onSuccess: () => notify.success('Solicitud eliminada.'),
        onError: () => notify.error('No se pudo eliminar la solicitud.'),
    });
};
</script>

<template>
    <Head :title="request.title" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Mis solicitudes', href: '/solicitudes' },
            { title: request.title, href: `/solicitudes/${request.id}` },
        ]"
    >
        <PageContainer>
            <div>
                <Button as-child variant="ghost" size="sm" class="-ml-2">
                    <Link href="/solicitudes">
                        <ArrowLeft />
                        Volver a mis solicitudes
                    </Link>
                </Button>
            </div>

            <PageHeader
                :title="request.title"
                :description="getTypeLabel(request.request_type)"
            >
                <template #actions>
                    <StatusBadge :status="request.status" class="h-6 px-2.5" />
                    <Button
                        v-if="request.status === 'pendiente'"
                        variant="outline"
                        class="text-destructive hover:bg-destructive-soft hover:text-destructive"
                        :disabled="deleting"
                        @click="deleteRequest"
                    >
                        <Spinner v-if="deleting" />
                        <Trash2 v-else />
                        Eliminar
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    <div
                        v-if="request.status === 'rechazado' && request.rejection_reason"
                        class="rounded-xl border border-destructive/20 bg-destructive-soft p-4"
                        role="note"
                    >
                        <h2 class="text-sm font-semibold text-destructive">
                            Motivo del rechazo
                        </h2>
                        <p class="mt-1 text-sm whitespace-pre-wrap text-foreground">
                            {{ request.rejection_reason }}
                        </p>
                    </div>

                    <SectionCard title="Detalles">
                        <dl class="grid gap-4 text-sm">
                            <div>
                                <dt class="text-[13px] text-muted-foreground">
                                    Tipo de solicitud
                                </dt>
                                <dd class="mt-0.5 font-medium">
                                    {{ getTypeLabel(request.request_type) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-[13px] text-muted-foreground">
                                    Asunto
                                </dt>
                                <dd class="mt-0.5 font-medium">
                                    {{ request.title }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-[13px] text-muted-foreground">
                                    Descripción
                                </dt>
                                <dd
                                    class="mt-0.5 max-w-[70ch] break-words whitespace-pre-wrap"
                                    :class="
                                        request.description
                                            ? ''
                                            : 'text-muted-foreground'
                                    "
                                >
                                    {{ request.description || 'Sin descripción.' }}
                                </dd>
                            </div>
                        </dl>
                    </SectionCard>

                    <SectionCard title="Historial de decisiones">
                        <RequestTimeline :approvals="approvals" />
                        <p
                            v-if="!approvals.length"
                            class="text-sm text-muted-foreground"
                        >
                            Todavía no hay decisiones. Te avisaremos cuando
                            Recursos Humanos la revise.
                        </p>
                    </SectionCard>
                </div>

                <SectionCard title="Información" class="self-start">
                    <dl class="grid gap-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Estado</dt>
                            <dd><StatusBadge :status="request.status" /></dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Creada</dt>
                            <dd class="text-right">
                                {{ formatRequestDate(request.created_at, true) }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">
                                Última actualización
                            </dt>
                            <dd class="text-right">
                                {{ formatRequestDate(request.updated_at, true) }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">N.º de solicitud</dt>
                            <dd class="tabular-nums">#{{ request.id }}</dd>
                        </div>
                    </dl>
                </SectionCard>
            </div>
        </PageContainer>
    </AppLayout>
</template>
