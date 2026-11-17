<script setup lang="ts">
import { UserCog } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import EmptyState from '@/components/app/EmptyState.vue';
import SectionCard from '@/components/app/SectionCard.vue';
import StatusBadge from '@/components/app/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { normalizeForSearch } from '@/lib/search';
import type { SystemAdmin } from './types';

const props = defineProps<{ admins: SystemAdmin[] }>();
const emit = defineEmits<{
    activate: [admin: SystemAdmin];
    deactivate: [admin: SystemAdmin];
}>();

const search = ref('');

const filtered = computed(() => {
    const term = normalizeForSearch(search.value);
    if (!term) return props.admins;
    return props.admins.filter((a) =>
        normalizeForSearch(`${a.name} ${a.email}`).includes(term),
    );
});
</script>

<template>
    <SectionCard
        title="Administradores"
        description="Activa, cambia o desactiva la suscripción de cada administrador."
        flush
    >
        <template #actions>
            <Input
                v-model="search"
                type="search"
                placeholder="Buscar por nombre o correo…"
                aria-label="Buscar administradores"
                class="h-8 w-full sm:w-64"
            />
        </template>

        <EmptyState
            v-if="filtered.length === 0"
            :icon="UserCog"
            compact
            :title="
                admins.length ? 'Sin coincidencias' : 'No hay administradores'
            "
            :description="
                admins.length
                    ? 'Prueba con otro término de búsqueda.'
                    : undefined
            "
        />

        <div v-else class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Administrador</th>
                        <th>Rol</th>
                        <th>Suscripción</th>
                        <th>Días restantes</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="admin in filtered" :key="admin.id">
                        <td>
                            <div class="font-medium">{{ admin.name }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ admin.email }}
                            </div>
                        </td>
                        <td>
                            <StatusBadge
                                :dot="false"
                                :tone="
                                    admin.role === 'superadmin'
                                        ? 'primary'
                                        : 'neutral'
                                "
                                :label="
                                    admin.role === 'superadmin'
                                        ? 'Superadmin'
                                        : 'Admin'
                                "
                            />
                        </td>
                        <td>
                            <StatusBadge
                                :tone="
                                    admin.has_active_subscription
                                        ? 'success'
                                        : 'destructive'
                                "
                                :label="
                                    admin.has_active_subscription
                                        ? 'Activa'
                                        : 'Sin suscripción'
                                "
                            />
                        </td>
                        <td class="tabular-nums">
                            <span
                                v-if="admin.has_active_subscription"
                                :class="
                                    admin.subscription_days_remaining <= 5
                                        ? 'font-medium text-destructive'
                                        : ''
                                "
                            >
                                {{ admin.subscription_days_remaining }} días
                            </span>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td>
                            <div
                                v-if="admin.role !== 'superadmin'"
                                class="flex justify-end gap-2"
                            >
                                <Button
                                    size="sm"
                                    :variant="
                                        admin.has_active_subscription
                                            ? 'outline'
                                            : 'default'
                                    "
                                    @click="emit('activate', admin)"
                                >
                                    {{
                                        admin.has_active_subscription
                                            ? 'Cambiar plan'
                                            : 'Activar'
                                    }}
                                </Button>
                                <Button
                                    v-if="admin.has_active_subscription"
                                    size="sm"
                                    variant="ghost"
                                    class="text-destructive hover:bg-destructive-soft hover:text-destructive"
                                    @click="emit('deactivate', admin)"
                                >
                                    Desactivar
                                </Button>
                            </div>
                            <span
                                v-else
                                class="block text-right text-xs text-muted-foreground"
                                >No aplica</span
                            >
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </SectionCard>
</template>
