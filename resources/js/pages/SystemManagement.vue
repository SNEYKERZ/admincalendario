<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { Cog } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import PageContainer from '@/components/app/PageContainer.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import AnnouncementFormDialog from '@/components/system/AnnouncementFormDialog.vue';
import PlanFormDialog from '@/components/system/PlanFormDialog.vue';
import SubscriptionDialog from '@/components/system/SubscriptionDialog.vue';
import SystemAdminsTab from '@/components/system/SystemAdminsTab.vue';
import SystemAnnouncementsTab from '@/components/system/SystemAnnouncementsTab.vue';
import SystemPlansTab from '@/components/system/SystemPlansTab.vue';
import SystemSettingsTab from '@/components/system/SystemSettingsTab.vue';
import { validationErrors } from '@/components/system/types';
import type {
    Announcement,
    AnnouncementAdmin,
    AnnouncementForm,
    PlanForm,
    SubscriptionPlan,
    SubscriptionSettings,
    SystemAdmin,
} from '@/components/system/types';
import { Skeleton } from '@/components/ui/skeleton';
import { useConfirm } from '@/composables/useConfirm';
import { useNotification } from '@/composables/useNotification';
import AppLayout from '@/layouts/AppLayout.vue';
import { cn } from '@/lib/utils';

const notify = useNotification();
const confirm = useConfirm();

const loading = ref(true);
const savingSettings = ref(false);
const savingPlan = ref(false);
const savingSubscription = ref(false);
const savingAnnouncement = ref(false);

const settings = ref<SubscriptionSettings>({
    cop_reference_amount: 0,
    usd_reference_amount: 0,
    show_ads_days_before: 5,
    enable_payments: false,
    payment_gateway: null,
    conversion_rate: 0,
});

const plans = ref<SubscriptionPlan[]>([]);
const admins = ref<SystemAdmin[]>([]);
const announcementAdmins = ref<AnnouncementAdmin[]>([]);
const announcements = ref<Announcement[]>([]);

const showPlanModal = ref(false);
const showSubscriptionModal = ref(false);
const showAnnouncementModal = ref(false);
const editingPlan = ref<SubscriptionPlan | null>(null);
const editingAnnouncement = ref<Announcement | null>(null);
const selectedAdmin = ref<SystemAdmin | null>(null);
const planErrors = ref<Record<string, string>>({});
const announcementErrors = ref<Record<string, string>>({});

const emptyPlanForm = (): PlanForm => ({
    name: '',
    duration_days: 30,
    price_cop: 0,
    price_usd: 0,
    discount_percentage: 0,
    original_price_cop: null,
    original_price_usd: null,
    is_active: true,
    display_order: plans.value.length + 1,
    description: '',
});

const planForm = ref<PlanForm>(emptyPlanForm());
const subscriptionPlanId = ref<number | null>(null);
const announcementForm = ref<AnnouncementForm>({
    title: '',
    message: '',
    type: 'general',
    days_before: null,
    is_active: true,
});

type TabKey = 'settings' | 'plans' | 'admins' | 'announcements';
const activeTab = ref<TabKey>('settings');
const tabs: { key: TabKey; label: string }[] = [
    { key: 'settings', label: 'Configuración' },
    { key: 'plans', label: 'Planes' },
    { key: 'admins', label: 'Administradores' },
    { key: 'announcements', label: 'Anuncios' },
];

const onTabKeydown = (event: KeyboardEvent, index: number) => {
    if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;
    event.preventDefault();
    const delta = event.key === 'ArrowRight' ? 1 : -1;
    const next = (index + delta + tabs.length) % tabs.length;
    activeTab.value = tabs[next].key;
    document.getElementById(`tab-${tabs[next].key}`)?.focus();
};

const loadData = async () => {
    try {
        const res = await axios.get('/gestion-sistema/api/data');
        if (res.data.settings) {
            settings.value = { ...settings.value, ...res.data.settings };
        }
        if (res.data.plans) plans.value = res.data.plans;
        if (res.data.admins) admins.value = res.data.admins;
        if (res.data.announcement_admins) {
            announcementAdmins.value = res.data.announcement_admins;
        }

        const announcementsRes = await axios.get(
            '/gestion-sistema/api/announcements',
        );
        announcements.value = announcementsRes.data.announcements || [];
    } catch {
        notify.error('No se pudieron cargar los datos del sistema.');
    } finally {
        loading.value = false;
    }
};

const saveSettings = async () => {
    savingSettings.value = true;
    try {
        await axios.put('/gestion-sistema/settings', settings.value);
        if (
            settings.value.cop_reference_amount &&
            settings.value.usd_reference_amount
        ) {
            await axios.post('/gestion-sistema/recalculate-prices');
            await loadData();
        }
        notify.success('Configuración guardada');
    } catch (e: any) {
        notify.error(
            e?.response?.data?.message ||
                'No se pudo guardar la configuración.',
        );
    } finally {
        savingSettings.value = false;
    }
};

const openPlanModal = (plan: SubscriptionPlan | null = null) => {
    planErrors.value = {};
    if (plan) {
        editingPlan.value = plan;
        planForm.value = {
            name: plan.name,
            duration_days: plan.duration_days,
            price_cop: plan.price_cop,
            price_usd: plan.price_usd,
            discount_percentage: plan.discount_percentage,
            original_price_cop: plan.original_price_cop,
            original_price_usd: plan.original_price_usd,
            is_active: plan.is_active,
            display_order: plan.display_order,
            description: plan.description,
        };
    } else {
        editingPlan.value = null;
        planForm.value = emptyPlanForm();
    }
    showPlanModal.value = true;
};

const savePlan = async () => {
    savingPlan.value = true;
    planErrors.value = {};
    try {
        if (editingPlan.value) {
            await axios.put(
                `/gestion-sistema/plans/${editingPlan.value.id}`,
                planForm.value,
            );
            notify.success('Plan actualizado');
        } else {
            await axios.post('/gestion-sistema/plans', planForm.value);
            notify.success('Plan creado');
        }
        showPlanModal.value = false;
        await loadData();
    } catch (e: any) {
        planErrors.value = validationErrors(e);
        notify.error(e?.response?.data?.message || 'No se pudo guardar el plan.');
    } finally {
        savingPlan.value = false;
    }
};

const deletePlan = async (plan: SubscriptionPlan) => {
    const ok = await confirm({
        title: 'Eliminar plan',
        description: `Se eliminará el plan "${plan.name}". Esta acción no se puede deshacer.`,
        variant: 'destructive',
    });
    if (!ok) return;
    try {
        await axios.delete(`/gestion-sistema/plans/${plan.id}`);
        notify.success('Plan eliminado');
        await loadData();
    } catch (e: any) {
        notify.error(
            e?.response?.data?.message || 'No se pudo eliminar el plan.',
        );
    }
};

const openSubscriptionModal = (admin: SystemAdmin) => {
    selectedAdmin.value = admin;
    subscriptionPlanId.value = null;
    showSubscriptionModal.value = true;
};

const activateSubscription = async () => {
    if (!selectedAdmin.value || !subscriptionPlanId.value) {
        notify.error('Selecciona un plan');
        return;
    }
    savingSubscription.value = true;
    try {
        await axios.post('/gestion-sistema/subscription/activate', {
            user_id: selectedAdmin.value.id,
            plan_id: subscriptionPlanId.value,
        });
        notify.success('Suscripción activada');
        showSubscriptionModal.value = false;
        await loadData();
    } catch (e: any) {
        notify.error(
            e?.response?.data?.message || 'No se pudo activar la suscripción.',
        );
    } finally {
        savingSubscription.value = false;
    }
};

const deactivateSubscription = async (admin: SystemAdmin) => {
    const ok = await confirm({
        title: 'Desactivar suscripción',
        description: `${admin.name} perderá el acceso a las funciones de pago de inmediato.`,
        confirmText: 'Desactivar',
        variant: 'destructive',
    });
    if (!ok) return;
    try {
        await axios.post('/gestion-sistema/subscription/deactivate', {
            user_id: admin.id,
        });
        notify.success('Suscripción desactivada');
        await loadData();
    } catch (e: any) {
        notify.error(
            e?.response?.data?.message ||
                'No se pudo desactivar la suscripción.',
        );
    }
};

const openAnnouncementModal = (announcement: Announcement | null = null) => {
    announcementErrors.value = {};
    if (announcement) {
        editingAnnouncement.value = announcement;
        announcementForm.value = {
            title: announcement.title,
            message: announcement.message,
            type: announcement.type,
            days_before: announcement.days_before,
            is_active: announcement.is_active,
        };
    } else {
        editingAnnouncement.value = null;
        announcementForm.value = {
            title: '',
            message: '',
            type: 'general',
            days_before: null,
            is_active: true,
        };
    }
    showAnnouncementModal.value = true;
};

const saveAnnouncement = async () => {
    savingAnnouncement.value = true;
    announcementErrors.value = {};
    try {
        if (editingAnnouncement.value) {
            await axios.put(
                `/gestion-sistema/announcements/${editingAnnouncement.value.id}`,
                announcementForm.value,
            );
            notify.success('Anuncio actualizado');
        } else {
            await axios.post(
                '/gestion-sistema/announcements',
                announcementForm.value,
            );
            notify.success('Anuncio creado');
        }
        showAnnouncementModal.value = false;
        await loadData();
    } catch (e: any) {
        announcementErrors.value = validationErrors(e);
        notify.error(
            e?.response?.data?.message || 'No se pudo guardar el anuncio.',
        );
    } finally {
        savingAnnouncement.value = false;
    }
};

const deleteAnnouncement = async (announcement: Announcement) => {
    const ok = await confirm({
        title: 'Eliminar anuncio',
        description: `Se eliminará el anuncio "${announcement.title}".`,
        variant: 'destructive',
    });
    if (!ok) return;
    try {
        await axios.delete(`/gestion-sistema/announcements/${announcement.id}`);
        notify.success('Anuncio eliminado');
        await loadData();
    } catch (e: any) {
        notify.error(
            e?.response?.data?.message || 'No se pudo eliminar el anuncio.',
        );
    }
};

onMounted(loadData);
</script>

<template>
    <Head title="Gestión del sistema" />
    <AppLayout>
        <PageContainer>
            <PageHeader
                title="Gestión del sistema"
                description="Suscripciones, planes, administradores y anuncios de la plataforma."
                :icon="Cog"
            />

            <div
                role="tablist"
                aria-label="Secciones de gestión del sistema"
                class="-mb-2 flex gap-1 overflow-x-auto border-b"
            >
                <button
                    v-for="(tab, index) in tabs"
                    :id="`tab-${tab.key}`"
                    :key="tab.key"
                    type="button"
                    role="tab"
                    :aria-selected="activeTab === tab.key"
                    :aria-controls="`panel-${tab.key}`"
                    :tabindex="activeTab === tab.key ? 0 : -1"
                    :class="
                        cn(
                            '-mb-px flex shrink-0 items-center gap-2 border-b-2 px-3 py-2 text-sm font-medium transition-colors',
                            activeTab === tab.key
                                ? 'border-primary text-foreground'
                                : 'border-transparent text-muted-foreground hover:text-foreground',
                        )
                    "
                    @click="activeTab = tab.key"
                    @keydown="onTabKeydown($event, index)"
                >
                    {{ tab.label }}
                    <span
                        v-if="
                            tab.key === 'announcements' &&
                            announcementAdmins.length > 0
                        "
                        class="rounded-full bg-warning-soft px-1.5 text-xs font-semibold text-warning tabular-nums"
                        :aria-label="`${announcementAdmins.length} administradores requieren atención`"
                        >{{ announcementAdmins.length }}</span
                    >
                </button>
            </div>

            <div v-if="loading" class="grid gap-4" aria-busy="true">
                <Skeleton class="h-40 w-full rounded-xl" />
                <div class="grid gap-4 md:grid-cols-2">
                    <Skeleton class="h-32 rounded-xl" />
                    <Skeleton class="h-32 rounded-xl" />
                </div>
            </div>

            <div
                v-else
                :id="`panel-${activeTab}`"
                role="tabpanel"
                :aria-labelledby="`tab-${activeTab}`"
            >
                <SystemSettingsTab
                    v-if="activeTab === 'settings'"
                    v-model:settings="settings"
                    :saving="savingSettings"
                    @save="saveSettings"
                />
                <SystemPlansTab
                    v-else-if="activeTab === 'plans'"
                    :plans="plans"
                    @create="openPlanModal()"
                    @edit="openPlanModal"
                    @delete="deletePlan"
                />
                <SystemAdminsTab
                    v-else-if="activeTab === 'admins'"
                    :admins="admins"
                    @activate="openSubscriptionModal"
                    @deactivate="deactivateSubscription"
                />
                <SystemAnnouncementsTab
                    v-else
                    :announcements="announcements"
                    :announcement-admins="announcementAdmins"
                    @create="openAnnouncementModal()"
                    @edit="openAnnouncementModal"
                    @delete="deleteAnnouncement"
                />
            </div>
        </PageContainer>

        <PlanFormDialog
            v-model:open="showPlanModal"
            v-model:form="planForm"
            :editing="!!editingPlan"
            :saving="savingPlan"
            :errors="planErrors"
            @save="savePlan"
        />
        <SubscriptionDialog
            v-model:open="showSubscriptionModal"
            v-model:plan-id="subscriptionPlanId"
            :admin="selectedAdmin"
            :plans="plans"
            :saving="savingSubscription"
            @save="activateSubscription"
        />
        <AnnouncementFormDialog
            v-model:open="showAnnouncementModal"
            v-model:form="announcementForm"
            :editing="!!editingAnnouncement"
            :saving="savingAnnouncement"
            :errors="announcementErrors"
            @save="saveAnnouncement"
        />
    </AppLayout>
</template>
