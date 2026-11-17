<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Building2, Eye, Search, UserRoundCog } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import EmptyState from '@/components/app/EmptyState.vue';
import PageContainer from '@/components/app/PageContainer.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import SectionCard from '@/components/app/SectionCard.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useConfirm } from '@/composables/useConfirm';
import { useNotification } from '@/composables/useNotification';
import { useSuperAdminContext } from '@/composables/useSuperAdminContext';
import AppLayout from '@/layouts/AppLayout.vue';
import { normalizeForSearch } from '@/lib/search';

interface Tenant {
    id: number;
    name: string;
    slug: string;
    domain: string | null;
    is_active: boolean;
    is_main: boolean;
    user_count: number;
    absence_count: number;
    admin_id: number | null;
    admin_name: string;
    admin_email: string;
    created_at: string;
}

const props = defineProps<{
    tenants: Tenant[];
}>();

const searchQuery = ref('');
const impersonatingId = ref<number | null>(null);
const { startImpersonation, loading } = useSuperAdminContext();
const confirm = useConfirm();
const notify = useNotification();

const filteredTenants = computed(() => {
    const query = normalizeForSearch(searchQuery.value);
    if (!query) return props.tenants;
    return props.tenants.filter((t) =>
        normalizeForSearch(
            `${t.name} ${t.slug} ${t.domain ?? ''} ${t.admin_name ?? ''} ${t.admin_email ?? ''}`,
        ).includes(query),
    );
});

const impersonate = async (tenant: Tenant) => {
    if (!tenant.admin_id) {
        notify.warning('Esta empresa no tiene un administrador asignado.');
        return;
    }
    const ok = await confirm({
        title: 'Entrar como administrador',
        description: `Verás la aplicación como ${tenant.admin_name} (${tenant.name}). Podrás salir en cualquier momento desde el aviso superior.`,
        confirmText: 'Entrar como admin',
    });
    if (!ok) return;
    impersonatingId.value = tenant.id;
    await startImpersonation(tenant.admin_id);
    impersonatingId.value = null;
};
</script>

<template>
    <Head title="Empresas" />
    <AppLayout>
        <PageContainer>
            <PageHeader
                title="Empresas (tenants)"
                description="Administra todas las empresas registradas en la plataforma."
                :icon="Building2"
            >
                <template #actions>
                    <Button variant="outline" as-child>
                        <Link href="/superadmin/dashboard">
                            <ArrowLeft /> Volver al resumen
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <SectionCard flush>
                <template #header>
                    <div class="relative w-full sm:max-w-sm">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <Input
                            v-model="searchQuery"
                            type="search"
                            placeholder="Buscar por nombre, dominio o administrador…"
                            aria-label="Buscar empresas"
                            class="pl-8"
                        />
                    </div>
                </template>
                <template #actions>
                    <span class="text-[13px] text-muted-foreground tabular-nums">
                        {{ filteredTenants.length }} de {{ tenants.length }}
                    </span>
                </template>

                <EmptyState
                    v-if="filteredTenants.length === 0"
                    :icon="Building2"
                    :title="
                        tenants.length
                            ? 'No se encontraron empresas'
                            : 'Aún no hay empresas'
                    "
                    :description="
                        tenants.length
                            ? 'Prueba con otro término de búsqueda.'
                            : undefined
                    "
                >
                    <Button
                        v-if="searchQuery"
                        variant="outline"
                        @click="searchQuery = ''"
                    >
                        Limpiar búsqueda
                    </Button>
                </EmptyState>

                <div v-else class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Empresa</th>
                                <th>Dominio</th>
                                <th>Administrador</th>
                                <th class="text-right">Usuarios</th>
                                <th>Estado</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="tenant in filteredTenants" :key="tenant.id">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex size-8 shrink-0 items-center justify-center rounded-md bg-primary-soft text-sm font-semibold text-primary"
                                            aria-hidden="true"
                                        >
                                            {{
                                                tenant.name
                                                    .charAt(0)
                                                    .toUpperCase()
                                            }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-medium">
                                                {{ tenant.name }}
                                                <span
                                                    v-if="tenant.is_main"
                                                    class="ml-1 text-xs font-normal text-muted-foreground"
                                                    >(principal)</span
                                                >
                                            </p>
                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                @{{ tenant.slug }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap text-muted-foreground">
                                    {{
                                        tenant.domain ||
                                        `${tenant.slug}.ausentra.com`
                                    }}
                                </td>
                                <td>
                                    <p class="font-medium">
                                        {{ tenant.admin_name || '—' }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ tenant.admin_email }}
                                    </p>
                                </td>
                                <td class="text-right tabular-nums">
                                    {{ tenant.user_count }}
                                </td>
                                <td>
                                    <StatusBadge
                                        :status="
                                            tenant.is_active
                                                ? 'activo'
                                                : 'inactivo'
                                        "
                                    />
                                </td>
                                <td>
                                    <div class="flex justify-end gap-1">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            as-child
                                        >
                                            <Link
                                                :href="`/superadmin/tenants/${tenant.id}`"
                                                :aria-label="`Ver detalle de ${tenant.name}`"
                                            >
                                                <Eye /> Ver
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            :disabled="loading || !tenant.admin_id"
                                            :title="
                                                tenant.admin_id
                                                    ? 'Entrar como el administrador de esta empresa'
                                                    : 'Sin administrador asignado'
                                            "
                                            @click="impersonate(tenant)"
                                        >
                                            <Spinner
                                                v-if="impersonatingId === tenant.id"
                                            />
                                            <UserRoundCog v-else />
                                            Entrar como admin
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </SectionCard>
        </PageContainer>
    </AppLayout>
</template>
