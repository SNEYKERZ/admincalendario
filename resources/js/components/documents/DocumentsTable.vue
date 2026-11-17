<script setup lang="ts">
import {
    Download,
    FileSearch,
    FileSignature,
    History,
    MoreHorizontal,
    Pencil,
    Search,
    Send,
    Trash2,
    X,
    XCircle,
} from 'lucide-vue-next';
import { computed } from 'vue';
import EmptyState from '@/components/app/EmptyState.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import {
    documentTypeLabel,
    formatDocDate,
    formatFileSize,
    modeLabel,
    signatureBadge,
} from './types';
import type { DocumentFilters, DocumentRow, UserOption } from './types';

defineProps<{
    documents: DocumentRow[];
    totalCount: number;
    loading: boolean;
    users: UserOption[];
    documentTypes: string[];
    isAdmin: boolean;
    busyId: number | null;
    canEdit: (doc: DocumentRow) => boolean;
    canDelete: (doc: DocumentRow) => boolean;
    canRequestSignature: (doc: DocumentRow) => boolean;
    canSign: (doc: DocumentRow) => boolean;
}>();

const filters = defineModel<DocumentFilters>('filters', { required: true });

const emit = defineEmits<{
    edit: [doc: DocumentRow];
    requestSignature: [doc: DocumentRow];
    sign: [doc: DocumentRow];
    reject: [doc: DocumentRow];
    history: [doc: DocumentRow];
    remove: [doc: DocumentRow];
}>();

const hasActiveFilters = computed(
    () =>
        !!filters.value.search ||
        filters.value.user_id !== 'all' ||
        filters.value.document_type !== 'all' ||
        filters.value.document_mode !== 'all' ||
        filters.value.signature_status !== 'all',
);

const clearFilters = () => {
    filters.value = {
        search: '',
        user_id: 'all',
        document_type: 'all',
        document_mode: 'all',
        signature_status: 'all',
    };
};

</script>

<template>
    <section class="min-w-0 overflow-hidden rounded-xl border bg-card">
        <div class="flex flex-col gap-3 border-b px-4 py-3 sm:px-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-[15px] font-semibold tracking-[-0.01em]">
                        Documentos
                    </h2>
                    <p class="text-[13px] text-muted-foreground" aria-live="polite">
                        <template v-if="hasActiveFilters">
                            {{ documents.length }} de {{ totalCount }} con los filtros actuales
                        </template>
                        <template v-else>{{ totalCount }} en total</template>
                    </p>
                </div>
                <Button
                    v-if="hasActiveFilters"
                    variant="ghost"
                    size="sm"
                    @click="clearFilters"
                >
                    <X /> Limpiar filtros
                </Button>
            </div>
            <div
                class="grid gap-2 sm:grid-cols-2"
                :class="isAdmin ? 'lg:grid-cols-5' : 'lg:grid-cols-4'"
            >
                <div class="relative sm:col-span-2 lg:col-span-1">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        v-model="filters.search"
                        type="search"
                        class="pl-8"
                        placeholder="Buscar por título, archivo o persona"
                        aria-label="Buscar documentos"
                    />
                </div>
                <select
                    v-if="isAdmin"
                    v-model="filters.user_id"
                    class="input"
                    aria-label="Filtrar por colaborador"
                >
                    <option value="all">Todos los colaboradores</option>
                    <option v-for="user in users" :key="user.id" :value="String(user.id)">
                        {{ user.name }}
                    </option>
                </select>
                <select
                    v-model="filters.document_type"
                    class="input"
                    aria-label="Filtrar por tipo de documento"
                >
                    <option value="all">Todos los tipos</option>
                    <option v-for="type in documentTypes" :key="type" :value="type">
                        {{ documentTypeLabel(type) }}
                    </option>
                </select>
                <select
                    v-model="filters.document_mode"
                    class="input"
                    aria-label="Filtrar por modalidad"
                >
                    <option value="all">Todas las modalidades</option>
                    <option value="template">General (plantilla)</option>
                    <option value="derived">Derivado</option>
                    <option value="user">Por usuario</option>
                </select>
                <select
                    v-model="filters.signature_status"
                    class="input"
                    aria-label="Filtrar por estado de firma"
                >
                    <option value="all">Cualquier estado de firma</option>
                    <option value="pending">Firma pendiente</option>
                    <option value="signed">Firmado</option>
                    <option value="rejected">Rechazado</option>
                    <option value="not_required">Sin firma requerida</option>
                </select>
            </div>
        </div>

        <!-- Cargando -->
        <div v-if="loading" class="space-y-2 p-4" aria-busy="true">
            <Skeleton v-for="i in 5" :key="i" class="h-11 w-full" />
        </div>

        <!-- Vacío -->
        <EmptyState
            v-else-if="documents.length === 0"
            :icon="FileSearch"
            :title="hasActiveFilters ? 'Ningún documento coincide' : 'Aún no hay documentos'"
            :description="
                hasActiveFilters
                    ? 'Prueba con otros filtros o límpialos para ver todo.'
                    : 'Crea o sube el primer documento para empezar.'
            "
        >
            <Button v-if="hasActiveFilters" variant="outline" size="sm" @click="clearFilters">
                Limpiar filtros
            </Button>
        </EmptyState>

        <template v-else>
            <!-- Escritorio: tabla -->
            <div class="hidden overflow-x-auto md:block">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Modalidad</th>
                            <th>Colaborador</th>
                            <th>Firma</th>
                            <th>Creado</th>
                            <th class="text-right"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="doc in documents" :key="doc.id">
                            <td class="max-w-[360px]">
                                <p class="truncate font-medium">{{ doc.title }}</p>
                                <p class="truncate text-xs text-muted-foreground">
                                    {{ documentTypeLabel(doc.document_type) }} ·
                                    {{ doc.original_name || 'Generado desde plantilla' }}
                                    <template v-if="doc.file_size">
                                        · {{ formatFileSize(doc.file_size) }}
                                    </template>
                                </p>
                                <p
                                    v-if="doc.template"
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    Derivado de: {{ doc.template.title }}
                                </p>
                            </td>
                            <td class="text-muted-foreground">
                                {{ modeLabel(doc.document_mode) }}
                            </td>
                            <td>{{ doc.user?.name ?? 'Sin asignar' }}</td>
                            <td>
                                <StatusBadge
                                    :status="signatureBadge(doc.signature_status).status"
                                    :label="signatureBadge(doc.signature_status).label"
                                />
                                <p
                                    v-if="doc.signed_at"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    {{ formatDocDate(doc.signed_at, true) }}
                                </p>
                            </td>
                            <td class="whitespace-nowrap text-muted-foreground">
                                {{ formatDocDate(doc.created_at) }}
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <Button
                                        v-if="canSign(doc)"
                                        size="sm"
                                        @click="emit('sign', doc)"
                                    >
                                        <FileSignature /> Firmar
                                    </Button>
                                    <Button
                                        as="a"
                                        :href="doc.download_url"
                                        variant="ghost"
                                        size="icon-sm"
                                        :aria-label="`Descargar ${doc.title}`"
                                        :title="'Ver / descargar'"
                                    >
                                        <Download />
                                    </Button>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="icon-sm"
                                                :disabled="busyId === doc.id"
                                                :aria-label="`Más acciones para ${doc.title}`"
                                            >
                                                <MoreHorizontal />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end" class="w-48">
                                            <DropdownMenuItem
                                                v-if="canEdit(doc)"
                                                @select="emit('edit', doc)"
                                            >
                                                <Pencil /> Asignar / editar
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                v-if="canRequestSignature(doc)"
                                                @select="emit('requestSignature', doc)"
                                            >
                                                <Send /> Solicitar firma
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                v-if="canSign(doc)"
                                                @select="emit('reject', doc)"
                                            >
                                                <XCircle /> Rechazar
                                            </DropdownMenuItem>
                                            <DropdownMenuItem @select="emit('history', doc)">
                                                <History /> Ver historial
                                            </DropdownMenuItem>
                                            <template v-if="canDelete(doc)">
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem
                                                    class="text-destructive focus:text-destructive [&_svg]:!text-destructive"
                                                    @select="emit('remove', doc)"
                                                >
                                                    <Trash2 /> Eliminar
                                                </DropdownMenuItem>
                                            </template>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Móvil: tarjetas -->
            <ul class="divide-y md:hidden">
                <li v-for="doc in documents" :key="doc.id" class="space-y-2 px-4 py-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-medium">{{ doc.title }}</p>
                            <p class="truncate text-xs text-muted-foreground">
                                {{ doc.user?.name ?? 'Sin asignar' }} ·
                                {{ formatDocDate(doc.created_at) }}
                            </p>
                        </div>
                        <StatusBadge
                            :status="signatureBadge(doc.signature_status).status"
                            :label="signatureBadge(doc.signature_status).label"
                        />
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Button v-if="canSign(doc)" size="sm" @click="emit('sign', doc)">
                            <FileSignature /> Firmar
                        </Button>
                        <Button as="a" :href="doc.download_url" variant="outline" size="sm">
                            <Download /> Descargar
                        </Button>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :aria-label="`Más acciones para ${doc.title}`"
                                >
                                    <MoreHorizontal /> Más
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-48">
                                <DropdownMenuItem v-if="canEdit(doc)" @select="emit('edit', doc)">
                                    <Pencil /> Asignar / editar
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    v-if="canRequestSignature(doc)"
                                    @select="emit('requestSignature', doc)"
                                >
                                    <Send /> Solicitar firma
                                </DropdownMenuItem>
                                <DropdownMenuItem v-if="canSign(doc)" @select="emit('reject', doc)">
                                    <XCircle /> Rechazar
                                </DropdownMenuItem>
                                <DropdownMenuItem @select="emit('history', doc)">
                                    <History /> Ver historial
                                </DropdownMenuItem>
                                <template v-if="canDelete(doc)">
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem
                                        class="text-destructive focus:text-destructive [&_svg]:!text-destructive"
                                        @select="emit('remove', doc)"
                                    >
                                        <Trash2 /> Eliminar
                                    </DropdownMenuItem>
                                </template>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </li>
            </ul>
        </template>
    </section>
</template>
