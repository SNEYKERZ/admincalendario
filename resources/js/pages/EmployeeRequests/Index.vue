<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ChevronRight,
    ClipboardList,
    Plus,
    ShieldCheck,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import EmptyState from '@/components/app/EmptyState.vue';
import PageContainer from '@/components/app/PageContainer.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import SectionCard from '@/components/app/SectionCard.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import TablePagination from '@/components/app/TablePagination.vue';
import {
    formatRequestDate,
    getTypeLabel,
    REQUEST_STATUSES,
} from '@/components/requests/utils';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useConfirm } from '@/composables/useConfirm';
import { useNotification } from '@/composables/useNotification';
import AppLayout from '@/layouts/AppLayout.vue';
import { cn } from '@/lib/utils';

interface EmployeeRequest {
    id: number;
    request_type: string;
    status: string;
    title: string;
    description: string | null;
    created_at: string;
    updated_at: string;
}

interface PaginatedRequests {
    data: EmployeeRequest[];
    current_page: number;
    last_page: number;
    total?: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

const props = defineProps<{
    requests: PaginatedRequests;
}>();

const page = usePage();
const confirm = useConfirm();
const notify = useNotification();

const isAdmin = computed(() =>
    ['admin', 'superadmin'].includes((page.props as any).auth?.user?.role),
);

const selectedStatus = ref('');
const deletingId = ref<number | null>(null);

const allRequests = computed(() =>
    Array.isArray(props.requests?.data) ? props.requests.data : [],
);

const filteredRequests = computed(() =>
    selectedStatus.value
        ? allRequests.value.filter((r) => r.status === selectedStatus.value)
        : allRequests.value,
);

const countFor = (status: string) =>
    status
        ? allRequests.value.filter((r) => r.status === status).length
        : allRequests.value.length;

const deleteRequest = async (request: EmployeeRequest) => {
    const ok = await confirm({
        title: 'Eliminar solicitud',
        description: `Se eliminará «${request.title}». Esta acción no se puede deshacer.`,
        variant: 'destructive',
    });
    if (!ok) return;
    router.delete(`/solicitudes/${request.id}`, {
        preserveScroll: true,
        onStart: () => (deletingId.value = request.id),
        onFinish: () => (deletingId.value = null),
        onSuccess: () => notify.success('Solicitud eliminada.'),
        onError: () => notify.error('No se pudo eliminar la solicitud.'),
    });
};

const goToPage = (p: number) => {
    router.get('/solicitudes', { page: p }, { preserveScroll: true });
};
</script>

<template>
    <Head title="Mis solicitudes" />
    <AppLayout :breadcrumbs="[{ title: 'Mis solicitudes', href: '/solicitudes' }]">
        <PageContainer>
            <PageHeader
                title="Mis solicitudes"
                description="Pide certificados, permisos especiales o cambios de datos a Recursos Humanos y sigue su estado."
                :icon="ClipboardList"
            >
                <template #actions>
                    <Button v-if="isAdmin" as-child variant="outline">
                        <Link href="/admin/solicitudes">
                            <ShieldCheck />
                            Gestionar solicitudes del equipo
                        </Link>
                    </Button>
                    <Button as-child>
                        <Link href="/solicitudes/crear">
                            <Plus />
                            Nueva solicitud
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <SectionCard flush>
                <template #header>
                    <div
                        role="tablist"
                        aria-label="Filtrar por estado"
                        class="inline-flex w-full gap-1 overflow-x-auto rounded-lg bg-muted p-1 sm:w-auto"
                    >
                        <button
                            v-for="s in REQUEST_STATUSES"
                            :key="s.value"
                            type="button"
                            role="tab"
                            :aria-selected="selectedStatus === s.value"
                            :class="
                                cn(
                                    'inline-flex h-7 items-center gap-1.5 rounded-md px-3 text-[13px] font-medium whitespace-nowrap transition-colors',
                                    selectedStatus === s.value
                                        ? 'bg-card text-foreground shadow-xs'
                                        : 'text-muted-foreground hover:text-foreground',
                                )
                            "
                            @click="selectedStatus = s.value"
                        >
                            {{ s.label }}
                            <span
                                v-if="requests.last_page <= 1"
                                class="text-xs text-muted-foreground tabular-nums"
                                >{{ countFor(s.value) }}</span
                            >
                        </button>
                    </div>
                </template>

                <EmptyState
                    v-if="!filteredRequests.length"
                    :icon="ClipboardList"
                    :title="
                        allRequests.length
                            ? 'No hay solicitudes con este estado'
                            : 'Aún no has creado solicitudes'
                    "
                    :description="
                        allRequests.length
                            ? 'Prueba con otro filtro de estado.'
                            : 'Crea tu primera solicitud y Recursos Humanos la revisará.'
                    "
                >
                    <Button v-if="!allRequests.length" as-child size="sm">
                        <Link href="/solicitudes/crear">
                            <Plus />
                            Nueva solicitud
                        </Link>
                    </Button>
                </EmptyState>

                <template v-else>
                    <!-- Escritorio -->
                    <div class="hidden overflow-x-auto md:block">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Asunto</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th>Creada</th>
                                    <th class="text-right">
                                        <span class="sr-only">Acciones</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="request in filteredRequests"
                                    :key="request.id"
                                >
                                    <td class="max-w-sm">
                                        <Link
                                            :href="`/solicitudes/${request.id}`"
                                            class="block truncate font-medium hover:text-primary hover:underline"
                                        >
                                            {{ request.title }}
                                        </Link>
                                    </td>
                                    <td class="whitespace-nowrap text-muted-foreground">
                                        {{ getTypeLabel(request.request_type) }}
                                    </td>
                                    <td>
                                        <StatusBadge :status="request.status" />
                                    </td>
                                    <td class="whitespace-nowrap text-muted-foreground">
                                        {{ formatRequestDate(request.created_at) }}
                                    </td>
                                    <td class="text-right whitespace-nowrap">
                                        <div class="inline-flex gap-1">
                                            <Button
                                                v-if="request.status === 'pendiente'"
                                                variant="ghost"
                                                size="icon-sm"
                                                class="text-destructive hover:bg-destructive-soft hover:text-destructive"
                                                :aria-label="`Eliminar solicitud ${request.title}`"
                                                title="Eliminar"
                                                :disabled="deletingId === request.id"
                                                @click="deleteRequest(request)"
                                            >
                                                <Spinner
                                                    v-if="deletingId === request.id"
                                                />
                                                <Trash2 v-else />
                                            </Button>
                                            <Button as-child variant="ghost" size="sm">
                                                <Link :href="`/solicitudes/${request.id}`">
                                                    Ver
                                                    <ChevronRight />
                                                </Link>
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Móvil -->
                    <ul class="divide-y md:hidden">
                        <li v-for="request in filteredRequests" :key="request.id">
                            <Link
                                :href="`/solicitudes/${request.id}`"
                                class="flex items-center gap-3 px-4 py-3 hover:bg-muted/40"
                            >
                                <div class="min-w-0 flex-1 space-y-1">
                                    <p class="truncate text-sm font-medium">
                                        {{ request.title }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ getTypeLabel(request.request_type) }} ·
                                        {{ formatRequestDate(request.created_at) }}
                                    </p>
                                </div>
                                <StatusBadge :status="request.status" />
                                <ChevronRight
                                    class="size-4 text-muted-foreground"
                                    aria-hidden="true"
                                />
                            </Link>
                        </li>
                    </ul>
                </template>

                <template v-if="requests.last_page > 1" #footer>
                    <TablePagination
                        class="w-full"
                        :page="requests.current_page"
                        :last-page="requests.last_page"
                        :total="requests.total"
                        label="solicitudes"
                        @change="goToPage"
                    />
                </template>
            </SectionCard>
        </PageContainer>
    </AppLayout>
</template>
