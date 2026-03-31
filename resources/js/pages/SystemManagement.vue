<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

interface SubscriptionPlan {
    id: number;
    name: string;
    duration_days: number;
    price_cop: number;
    price_usd: number;
    discount_percentage: number;
    original_price_cop: number | null;
    original_price_usd: number | null;
    is_active: boolean;
    display_order: number;
    description: string;
}

interface SubscriptionSettings {
    conversion_rate: number;
    cop_reference_amount: number;
    usd_reference_amount: number;
    show_ads_days_before: number;
    enable_payments: boolean;
    payment_gateway: string | null;
}

interface Admin {
    id: number;
    name: string;
    email: string;
    role: string;
    has_active_subscription: boolean;
    subscription_days_remaining: number;
    subscription_expires_at: string | null;
}

interface AnnouncementAdmin {
    id: number;
    name: string;
    email: string;
    plan_name: string;
    expires_at: string;
    days_remaining: number;
}

interface Announcement {
    id: number;
    title: string;
    message: string;
    type: 'expiring' | 'expired' | 'general';
    days_before: number | null;
    is_active: boolean;
    created_at: string;
}

const toast = useToast();

const loading = ref(true);
const settings = ref<SubscriptionSettings>({
    cop_reference_amount: 0,
    usd_reference_amount: 0,
    show_ads_days_before: 5,
    enable_payments: false,
    payment_gateway: null,
    conversion_rate: 0,
});

const plans = ref<SubscriptionPlan[]>([]);
const admins = ref<Admin[]>([]);
const announcementAdmins = ref<AnnouncementAdmin[]>([]);
const announcements = ref<Announcement[]>([]);

const showPlanModal = ref(false);
const showSubscriptionModal = ref(false);
const showAnnouncementModal = ref(false);
const editingPlan = ref<SubscriptionPlan | null>(null);
const editingAnnouncement = ref<Announcement | null>(null);
const selectedAdmin = ref<Admin | null>(null);

const planForm = ref({
    name: '',
    duration_days: 30,
    price_cop: 0,
    price_usd: 0,
    discount_percentage: 0,
    original_price_cop: null as number | null,
    original_price_usd: null as number | null,
    is_active: true,
    display_order: 0,
    description: '',
});

const subscriptionForm = ref({
    plan_id: null as number | null,
});

const announcementForm = ref({
    title: '',
    message: '',
    type: 'general' as 'expiring' | 'expired' | 'general',
    days_before: null as number | null,
    is_active: true,
});

const activeTab = ref<string>('settings');

const loadData = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/gestion-sistema/api/data');
        // Merge API response with defaults to avoid undefined values
        if (res.data.settings) {
            settings.value = { ...settings.value, ...res.data.settings };
        }
        if (res.data.plans) {
            plans.value = res.data.plans;
        }
        if (res.data.admins) {
            admins.value = res.data.admins;
        }
        if (res.data.announcement_admins) {
            announcementAdmins.value = res.data.announcement_admins;
        }

        // Load announcements
        const announcementsRes = await axios.get(
            '/gestion-sistema/api/announcements',
        );
        announcements.value = announcementsRes.data.announcements || [];
    } catch (e) {
        toast.error('Error cargando datos');
    } finally {
        loading.value = false;
    }
};

const saveSettings = async () => {
    try {
        await axios.put('/gestion-sistema/settings', settings.value);
        toast.success('Configuración guardada');
        if (
            settings.value.cop_reference_amount &&
            settings.value.usd_reference_amount
        ) {
            await axios.post('/gestion-sistema/recalculate-prices');
            await loadData();
        }
    } catch (e) {
        toast.error('Error guardando configuración');
    }
};

const formatCurrency = (amount: number, currency: 'COP' | 'USD') => {
    if (currency === 'COP') {
        return new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
        }).format(amount);
    }
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

const openPlanModal = (plan: SubscriptionPlan | null = null) => {
    if (plan) {
        editingPlan.value = plan;
        planForm.value = {
            ...plan,
            original_price_cop: plan.original_price_cop,
            original_price_usd: plan.original_price_usd,
        };
    } else {
        editingPlan.value = null;
        planForm.value = {
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
        };
    }
    showPlanModal.value = true;
};

const savePlan = async () => {
    try {
        console.log('Saving plan:', planForm.value);
        if (editingPlan.value) {
            const res = await axios.put(
                `/gestion-sistema/plans/${editingPlan.value.id}`,
                planForm.value,
            );
            console.log('Plan updated:', res.data);
            toast.success('Plan actualizado');
        } else {
            const res = await axios.post(
                '/gestion-sistema/plans',
                planForm.value,
            );
            console.log('Plan created:', res.data);
            toast.success('Plan creado');
        }
        showPlanModal.value = false;
        await loadData();
    } catch (e: any) {
        console.error('Error saving plan:', e);
        toast.error(e.response?.data?.message || 'Error guardando plan');
    }
};

const deletePlan = async (plan: SubscriptionPlan) => {
    if (!confirm(`¿Eliminar el plan "${plan.name}"?`)) return;
    try {
        await axios.delete(`/gestion-sistema/plans/${plan.id}`);
        toast.success('Plan eliminado');
        await loadData();
    } catch (e: any) {
        toast.error(e.response?.data?.message || 'Error eliminando plan');
    }
};

const openSubscriptionModal = (admin: Admin) => {
    selectedAdmin.value = admin;
    subscriptionForm.value = { plan_id: null };
    showSubscriptionModal.value = true;
};

const activateSubscription = async () => {
    if (!selectedAdmin.value || !subscriptionForm.value.plan_id) {
        toast.error('Selecciona un plan');
        return;
    }
    try {
        await axios.post('/gestion-sistema/subscription/activate', {
            user_id: selectedAdmin.value.id,
            plan_id: subscriptionForm.value.plan_id,
        });
        toast.success('Suscripción activada');
        showSubscriptionModal.value = false;
        await loadData();
    } catch (e) {
        toast.error('Error activando suscripción');
    }
};

const deactivateSubscription = async (admin: Admin) => {
    if (!confirm(`¿Desactivar suscripción de ${admin.name}?`)) return;
    try {
        await axios.post('/gestion-sistema/subscription/deactivate', {
            user_id: admin.id,
        });
        toast.success('Suscripción desactivada');
        await loadData();
    } catch (e) {
        toast.error('Error desactivando suscripción');
    }
};

// Announcement CRUD functions
const openAnnouncementModal = (announcement: Announcement | null = null) => {
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
    try {
        if (editingAnnouncement.value) {
            await axios.put(
                `/gestion-sistema/announcements/${editingAnnouncement.value.id}`,
                announcementForm.value,
            );
            toast.success('Anuncio actualizado');
        } else {
            await axios.post(
                '/gestion-sistema/announcements',
                announcementForm.value,
            );
            toast.success('Anuncio creado');
        }
        showAnnouncementModal.value = false;
        await loadData();
    } catch (e: any) {
        toast.error(e.response?.data?.message || 'Error guardando anuncio');
    }
};

const deleteAnnouncement = async (announcement: Announcement) => {
    if (!confirm(`¿Eliminar el anuncio "${announcement.title}"?`)) return;
    try {
        await axios.delete(`/gestion-sistema/announcements/${announcement.id}`);
        toast.success('Anuncio eliminado');
        await loadData();
    } catch (e: any) {
        toast.error(e.response?.data?.message || 'Error eliminando anuncio');
    }
};

const calculateConversion = computed(() => {
    if (
        !settings.value ||
        settings.value.cop_reference_amount <= 0 ||
        settings.value.usd_reference_amount <= 0
    ) {
        return 0;
    }
    return (
        settings.value.usd_reference_amount /
        settings.value.cop_reference_amount
    );
});

onMounted(() => {
    loadData();
});
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4">
            <!-- Header -->
            <div v-if="loading" class="flex justify-center py-8">
                <span class="text-gray-500">Cargando...</span>
            </div>
            <div v-else>
                <div class="flex items-center justify-between">
                    <div>
                        <h1
                            class="text-2xl font-bold text-gray-900 dark:text-gray-100"
                        >
                            Gestión del Sistema
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Configuración de suscripciones y anuncios
                        </p>
                    </div>
                </div>

                <!-- Tabs -->
                <div
                    class="flex gap-2 border-b border-gray-200 dark:border-gray-700"
                >
                    <button
                        @click="activeTab = 'settings'"
                        :class="[
                            'px-4 py-2 text-sm font-medium transition-colors',
                            activeTab === 'settings'
                                ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400'
                                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400',
                        ]"
                    >
                        Configuración
                    </button>
                    <button
                        @click="activeTab = 'plans'"
                        :class="[
                            'px-4 py-2 text-sm font-medium transition-colors',
                            activeTab === 'plans'
                                ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400'
                                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400',
                        ]"
                    >
                        Planes
                    </button>
                    <button
                        @click="activeTab = 'admins'"
                        :class="[
                            'px-4 py-2 text-sm font-medium transition-colors',
                            activeTab === 'admins'
                                ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400'
                                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400',
                        ]"
                    >
                        Administradores
                    </button>
                    <button
                        @click="activeTab = 'announcements'"
                        :class="[
                            'px-4 py-2 text-sm font-medium transition-colors',
                            activeTab === 'announcements'
                                ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400'
                                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400',
                        ]"
                    >
                        Anuncios
                        <span
                            v-if="announcementAdmins?.length > 0"
                            class="ml-1 rounded-full bg-red-500 px-2 py-0.5 text-xs text-white"
                            >{{ announcementAdmins?.length }}</span
                        >
                    </button>
                </div>

                <!-- Settings Tab -->
                <div v-if="activeTab === 'settings'" class="space-y-6">
                    <div
                        class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <h2 class="mb-4 text-lg font-semibold">
                            Conversión de Moneda
                        </h2>
                        <p
                            class="mb-4 text-sm text-gray-500 dark:text-gray-400"
                        >
                            Establece la tasa de conversión entre dólares y
                            pesos colombianos.
                        </p>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="label"
                                    >Pesos Colombianos (COP)</label
                                >
                                <input
                                    v-model.number="
                                        settings.cop_reference_amount
                                    "
                                    type="number"
                                    class="input"
                                    placeholder="20000"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    Ej: 20,000 COP
                                </p>
                            </div>
                            <div>
                                <label class="label">Dólares (USD)</label>
                                <input
                                    v-model.number="
                                        settings.usd_reference_amount
                                    "
                                    type="number"
                                    class="input"
                                    placeholder="5"
                                    step="0.01"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    Ej: 5 USD
                                </p>
                            </div>
                        </div>
                        <div
                            class="mt-4 rounded-lg bg-blue-50 p-4 dark:bg-blue-900/30"
                        >
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                <strong>Tasa de conversión:</strong> 1 USD =
                                {{
                                    formatCurrency(
                                        1 / calculateConversion,
                                        'COP',
                                    )
                                }}
                            </p>
                        </div>
                        <div class="mt-4">
                            <button @click="saveSettings" class="btn-primary">
                                Guardar Configuración
                            </button>
                        </div>
                    </div>

                    <div
                        class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <h2 class="mb-4 text-lg font-semibold">Anuncios</h2>
                        <p
                            class="mb-4 text-sm text-gray-500 dark:text-gray-400"
                        >
                            Configura cuando se mostrarán los anuncios.
                        </p>
                        <div class="max-w-xs">
                            <label class="label"
                                >Días antes del vencimiento</label
                            >
                            <input
                                v-model.number="settings.show_ads_days_before"
                                type="number"
                                class="input"
                                min="1"
                                max="30"
                            />
                        </div>
                        <div class="mt-4">
                            <button @click="saveSettings" class="btn-primary">
                                Guardar Configuración
                            </button>
                        </div>
                    </div>

                    <div
                        class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <h2 class="mb-4 text-lg font-semibold">Pagos</h2>
                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-2">
                                <input
                                    v-model="settings.enable_payments"
                                    type="checkbox"
                                    class="h-4 w-4 rounded"
                                />
                                <span
                                    class="text-sm text-gray-700 dark:text-gray-300"
                                    >Habilitar pagos en línea</span
                                >
                            </label>
                        </div>
                        <div class="mt-4">
                            <button @click="saveSettings" class="btn-primary">
                                Guardar Configuración
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Plans Tab -->
                <div v-if="activeTab === 'plans'" class="space-y-4">
                    <div class="flex justify-end">
                        <button @click="openPlanModal()" class="btn-primary">
                            + Nuevo Plan
                        </button>
                    </div>

                    <div
                        v-if="plans?.length === 0"
                        class="rounded-xl border border-gray-200 bg-white p-8 text-center dark:border-gray-700 dark:bg-gray-800"
                    >
                        <p class="text-gray-500">
                            No hay planes de suscripción
                        </p>
                    </div>

                    <div v-else class="grid gap-4 md:grid-cols-3">
                        <div
                            v-for="plan in plans"
                            :key="plan.id"
                            class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold">
                                        {{ plan.name }}
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        {{ plan.duration_days }} días
                                    </p>
                                </div>
                                <span
                                    :class="[
                                        'rounded-full px-2.5 py-0.5 text-xs font-medium',
                                        plan.is_active
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-gray-100 text-gray-800',
                                    ]"
                                >
                                    {{ plan.is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </div>
                            <div class="mt-4 flex items-baseline gap-2">
                                <span
                                    v-if="plan.discount_percentage > 0"
                                    class="text-sm text-gray-400 line-through"
                                >
                                    {{
                                        formatCurrency(
                                            plan.original_price_cop ||
                                                plan.price_cop * 1.3,
                                            'COP',
                                        )
                                    }}
                                </span>
                                <span
                                    class="text-2xl font-bold text-gray-900"
                                    >{{
                                        formatCurrency(plan.price_cop, 'COP')
                                    }}</span
                                >
                                <span class="text-sm text-gray-500"
                                    >/
                                    {{
                                        formatCurrency(plan.price_usd, 'USD')
                                    }}</span
                                >
                            </div>
                            <div
                                v-if="plan.discount_percentage > 0"
                                class="mt-2"
                            >
                                <span
                                    class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800"
                                    >-{{ plan.discount_percentage }}%
                                    descuento</span
                                >
                            </div>
                            <p
                                v-if="plan.description"
                                class="mt-2 text-sm text-gray-500"
                            >
                                {{ plan.description }}
                            </p>
                            <div class="mt-4 flex gap-2">
                                <button
                                    @click="openPlanModal(plan)"
                                    class="btn-secondary text-sm"
                                >
                                    Editar
                                </button>
                                <button
                                    @click="deletePlan(plan)"
                                    class="btn-danger text-sm"
                                >
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Admins Tab -->
            <div v-if="activeTab === 'admins'" class="space-y-4">
                <div
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
                >
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500"
                                    >
                                        Administrador
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500"
                                    >
                                        Email
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500"
                                    >
                                        Rol
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500"
                                    >
                                        Suscripción
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500"
                                    >
                                        Días
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500"
                                    >
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-if="loading">
                                    <td
                                        colspan="6"
                                        class="py-8 text-center text-gray-500"
                                    >
                                        Cargando...
                                    </td>
                                </tr>
                                <tr
                                    v-for="admin in admins"
                                    :key="admin.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-4 py-3 font-medium">
                                        {{ admin.name }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ admin.email }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            :class="[
                                                'rounded-full px-2.5 py-0.5 text-xs font-medium',
                                                admin.role === 'superadmin'
                                                    ? 'bg-purple-100 text-purple-800'
                                                    : 'bg-blue-100 text-blue-800',
                                            ]"
                                        >
                                            {{
                                                admin.role === 'superadmin'
                                                    ? 'Superadmin'
                                                    : 'Admin'
                                            }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            :class="[
                                                'rounded-full px-2.5 py-0.5 text-xs font-medium',
                                                admin.has_active_subscription
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-red-100 text-red-800',
                                            ]"
                                        >
                                            {{
                                                admin.has_active_subscription
                                                    ? 'Activa'
                                                    : 'Sin suscripción'
                                            }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            v-if="admin.has_active_subscription"
                                            :class="[
                                                'font-medium',
                                                admin.subscription_days_remaining <=
                                                5
                                                    ? 'text-red-600'
                                                    : '',
                                            ]"
                                            >{{
                                                admin.subscription_days_remaining
                                            }}
                                            días</span
                                        >
                                        <span v-else class="text-gray-400"
                                            >-</span
                                        >
                                    </td>
                                    <td class="px-4 py-3">
                                        <button
                                            v-if="admin.role !== 'superadmin'"
                                            @click="
                                                openSubscriptionModal(admin)
                                            "
                                            class="btn-primary text-sm"
                                        >
                                            {{
                                                admin.has_active_subscription
                                                    ? 'Cambiar'
                                                    : 'Activar'
                                            }}
                                        </button>
                                        <button
                                            v-if="
                                                admin.has_active_subscription &&
                                                admin.role !== 'superadmin'
                                            "
                                            @click="
                                                deactivateSubscription(admin)
                                            "
                                            class="btn-danger ml-2 text-sm"
                                        >
                                            Desactivar
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Announcements Tab -->
            <div v-if="activeTab === 'announcements'" class="space-y-6">
                <!-- Create new announcement button -->
                <div class="flex justify-end">
                    <button
                        @click="openAnnouncementModal()"
                        class="btn-primary"
                    >
                        + Nuevo Anuncio
                    </button>
                </div>

                <!-- Announcements List -->
                <div
                    v-if="announcements.length === 0"
                    class="rounded-xl border border-gray-200 bg-white p-8 text-center dark:border-gray-700 dark:bg-gray-800"
                >
                    <p class="text-gray-500">No hay anuncios creados</p>
                </div>
                <div v-else class="space-y-4">
                    <h3 class="text-lg font-semibold">Anuncios Creados</h3>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div
                            v-for="announcement in announcements"
                            :key="announcement.id"
                            class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 class="font-semibold">
                                        {{ announcement.title }}
                                    </h4>
                                    <p class="mt-1 text-sm text-gray-500">
                                        {{ announcement.message }}
                                    </p>
                                </div>
                                <span
                                    :class="[
                                        'rounded-full px-2.5 py-0.5 text-xs font-medium',
                                        announcement.is_active
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-gray-100 text-gray-800',
                                    ]"
                                >
                                    {{
                                        announcement.is_active
                                            ? 'Activo'
                                            : 'Inactivo'
                                    }}
                                </span>
                            </div>
                            <div class="mt-3 text-sm text-gray-500">
                                <span
                                    :class="[
                                        'rounded-full px-2 py-0.5 text-xs',
                                        announcement.type === 'expiring'
                                            ? 'bg-amber-100 text-amber-800'
                                            : announcement.type === 'expired'
                                              ? 'bg-red-100 text-red-800'
                                              : 'bg-blue-100 text-blue-800',
                                    ]"
                                >
                                    {{
                                        announcement.type === 'expiring'
                                            ? 'Por vencer'
                                            : announcement.type === 'expired'
                                              ? 'Vencido'
                                              : 'General'
                                    }}
                                </span>
                                <span
                                    v-if="announcement.days_before"
                                    class="ml-2"
                                >
                                    ({{ announcement.days_before }} días antes)
                                </span>
                            </div>
                            <div class="mt-4 flex gap-2">
                                <button
                                    @click="openAnnouncementModal(announcement)"
                                    class="btn-secondary text-sm"
                                >
                                    Editar
                                </button>
                                <button
                                    @click="deleteAnnouncement(announcement)"
                                    class="btn-danger text-sm"
                                >
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admins needing attention -->
                <div
                    v-if="announcementAdmins.length > 0"
                    class="space-y-4 border-t pt-6"
                >
                    <div class="rounded-lg bg-amber-50 p-4">
                        <h3 class="font-medium text-amber-800">
                            {{ announcementAdmins.length }} administrador(es)
                            necesitan atención
                        </h3>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div
                            v-for="admin in announcementAdmins"
                            :key="admin.id"
                            class="rounded-xl border border-amber-200 bg-amber-50 p-6"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="font-semibold">
                                        {{ admin.name }}
                                    </h3>
                                    <p class="text-sm text-amber-700">
                                        {{ admin.email }}
                                    </p>
                                </div>
                                <span
                                    class="rounded-full bg-red-500 px-3 py-1 text-sm font-bold text-white"
                                    >{{ admin.days_remaining }} días</span
                                >
                            </div>
                            <div class="mt-3 text-sm text-amber-800">
                                <p>Plan: {{ admin.plan_name }}</p>
                                <p>
                                    Vence:
                                    {{
                                        new Date(
                                            admin.expires_at,
                                        ).toLocaleDateString('es-CO')
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Plan Modal -->
        <div
            v-if="showPlanModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            @click.self="showPlanModal = false"
        >
            <div
                class="w-full max-w-md rounded-xl bg-white p-6 dark:bg-gray-800"
            >
                <h2 class="mb-4 text-lg font-semibold">
                    {{ editingPlan ? 'Editar Plan' : 'Nuevo Plan' }}
                </h2>
                <div class="space-y-4">
                    <div>
                        <label class="label">Nombre</label>
                        <input
                            v-model="planForm.name"
                            type="text"
                            class="input"
                        />
                    </div>
                    <div>
                        <label class="label">Duración (días)</label>
                        <input
                            v-model.number="planForm.duration_days"
                            type="number"
                            class="input"
                        />
                    </div>
                    <div>
                        <label class="label">Precio (COP)</label>
                        <input
                            v-model.number="planForm.price_cop"
                            type="number"
                            class="input"
                        />
                    </div>
                    <div>
                        <label class="label">Descuento (%)</label>
                        <input
                            v-model.number="planForm.discount_percentage"
                            type="number"
                            min="0"
                            max="100"
                            class="input"
                            placeholder="0"
                        />
                    </div>
                    <div
                        v-if="planForm.discount_percentage > 0"
                        class="grid grid-cols-2 gap-4"
                    >
                        <div>
                            <label class="label">Precio Original (COP)</label>
                            <input
                                v-model.number="planForm.original_price_cop"
                                type="number"
                                class="input"
                            />
                        </div>
                        <div>
                            <label class="label">Precio Original (USD)</label>
                            <input
                                v-model.number="planForm.original_price_usd"
                                type="number"
                                step="0.01"
                                class="input"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="label">Descripción</label>
                        <textarea
                            v-model="planForm.description"
                            class="input"
                            rows="2"
                        ></textarea>
                    </div>
                    <label class="flex items-center gap-2">
                        <input
                            v-model="planForm.is_active"
                            type="checkbox"
                            class="h-4 w-4 rounded"
                        />
                        <span class="text-sm">Activo</span>
                    </label>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button
                        @click="showPlanModal = false"
                        class="btn-secondary"
                    >
                        Cancelar
                    </button>
                    <button @click="savePlan" class="btn-primary">
                        Guardar
                    </button>
                </div>
            </div>
        </div>

        <!-- Subscription Modal -->
        <div
            v-if="showSubscriptionModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            @click.self="showSubscriptionModal = false"
        >
            <div
                class="w-full max-w-md rounded-xl bg-white p-6 dark:bg-gray-800"
            >
                <h2 class="mb-4 text-lg font-semibold">Activar Suscripción</h2>
                <p class="mb-4 text-sm text-gray-500">
                    Selecciona el plan para {{ selectedAdmin?.name }}
                </p>
                <div class="space-y-3">
                    <label
                        v-for="plan in plans"
                        :key="plan.id"
                        class="flex cursor-pointer items-center justify-between rounded-lg border p-4 hover:bg-gray-50"
                        :class="
                            subscriptionForm.plan_id === plan.id
                                ? 'border-blue-500 bg-blue-50'
                                : 'border-gray-200'
                        "
                    >
                        <div class="flex items-center gap-3">
                            <input
                                v-model="subscriptionForm.plan_id"
                                type="radio"
                                :value="plan.id"
                                class="h-4 w-4"
                            />
                            <div>
                                <p class="font-medium">{{ plan.name }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ plan.duration_days }} días
                                </p>
                            </div>
                        </div>
                        <span class="font-semibold">{{
                            formatCurrency(plan.price_cop, 'COP')
                        }}</span>
                    </label>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button
                        @click="showSubscriptionModal = false"
                        class="btn-secondary"
                    >
                        Cancelar
                    </button>
                    <button @click="activateSubscription" class="btn-primary">
                        Activar
                    </button>
                </div>
            </div>
        </div>

        <!-- Announcement Modal -->
        <div
            v-if="showAnnouncementModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            @click.self="showAnnouncementModal = false"
        >
            <div
                class="w-full max-w-md rounded-xl bg-white p-6 dark:bg-gray-800"
            >
                <h2 class="mb-4 text-lg font-semibold">
                    {{
                        editingAnnouncement ? 'Editar Anuncio' : 'Nuevo Anuncio'
                    }}
                </h2>
                <div class="space-y-4">
                    <div>
                        <label class="label">Título</label>
                        <input
                            v-model="announcementForm.title"
                            type="text"
                            class="input"
                        />
                    </div>
                    <div>
                        <label class="label">Mensaje</label>
                        <textarea
                            v-model="announcementForm.message"
                            class="input"
                            rows="3"
                        ></textarea>
                    </div>
                    <div>
                        <label class="label">Tipo</label>
                        <select v-model="announcementForm.type" class="input">
                            <option value="general">General</option>
                            <option value="expiring">Por vencer</option>
                            <option value="expired">Vencido</option>
                        </select>
                    </div>
                    <div v-if="announcementForm.type !== 'general'">
                        <label class="label">Días antes del vencimiento</label>
                        <input
                            v-model.number="announcementForm.days_before"
                            type="number"
                            class="input"
                            min="1"
                            max="365"
                        />
                    </div>
                    <label class="flex items-center gap-2">
                        <input
                            v-model="announcementForm.is_active"
                            type="checkbox"
                            class="h-4 w-4 rounded"
                        />
                        <span class="text-sm">Activo</span>
                    </label>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button
                        @click="showAnnouncementModal = false"
                        class="btn-secondary"
                    >
                        Cancelar
                    </button>
                    <button @click="saveAnnouncement" class="btn-primary">
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.input {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    padding: 0.5rem;
    font-size: 0.875rem;
}
.label {
    display: block;
    margin-bottom: 0.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
}
.btn-primary {
    display: inline-flex;
    align-items: center;
    border-radius: 0.375rem;
    background-color: #2563eb;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: white;
}
.btn-primary:hover {
    background-color: #1d4ed8;
}
.btn-secondary {
    display: inline-flex;
    align-items: center;
    border-radius: 0.375rem;
    border: 1px solid #d1d5db;
    background-color: white;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
}
.btn-danger {
    display: inline-flex;
    align-items: center;
    border-radius: 0.375rem;
    background-color: #dc2626;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: white;
}
.btn-danger:hover {
    background-color: #b91c1c;
}
</style>
