<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

interface CompanySettings {
    id: number;
    company_name: string;
    company_logo: string | null;
    company_address: string;
    company_phone: string;
    company_email: string;
    company_identification: string;
    vacation_days_default: number;
    vacation_days_advance: number;
    workday_start: string;
    workday_end: string;
    allow_weekend_absences: boolean;
    allow_holiday_absences: boolean;
    require_approval_for_all: boolean;
    notification_email_enabled: boolean;
}

const toast = useToast();

const loading = ref(false);
const saving = ref(false);
const settings = ref<CompanySettings | null>(null);

const form = ref({
    company_name: '',
    company_address: '',
    company_phone: '',
    company_email: '',
    company_identification: '',
    vacation_days_default: 15,
    vacation_days_advance: 30,
    workday_start: '08:00',
    workday_end: '17:00',
    allow_weekend_absences: false,
    allow_holiday_absences: false,
    require_approval_for_all: true,
    notification_email_enabled: true,
    company_logo: null as File | null,
});

const logoPreview = computed(() => {
    if (form.value.company_logo && form.value.company_logo instanceof File) {
        return URL.createObjectURL(form.value.company_logo);
    }
    return settings.value?.company_logo
        ? `/storage/${settings.value.company_logo}`
        : null;
});

const loadSettings = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/settings/company/data');
        settings.value = res.data;
        form.value = {
            company_name: res.data.company_name || '',
            company_address: res.data.company_address || '',
            company_phone: res.data.company_phone || '',
            company_email: res.data.company_email || '',
            company_identification: res.data.company_identification || '',
            vacation_days_default: res.data.vacation_days_default || 15,
            vacation_days_advance: res.data.vacation_days_advance || 30,
            workday_start: res.data.workday_start || '08:00',
            workday_end: res.data.workday_end || '17:00',
            allow_weekend_absences: res.data.allow_weekend_absences || false,
            allow_holiday_absences: res.data.allow_holiday_absences || false,
            require_approval_for_all: res.data.require_approval_for_all ?? true,
            notification_email_enabled:
                res.data.notification_email_enabled ?? true,
            company_logo: null,
        };
    } catch (e) {
        toast.error('Error cargando configuración');
    } finally {
        loading.value = false;
    }
};

const saveSettings = async () => {
    saving.value = true;
    try {
        const formData = new FormData();
        Object.entries(form.value).forEach(([key, value]) => {
            if (key === 'company_logo' && value) {
                formData.append('company_logo', value);
            } else if (typeof value === 'boolean') {
                formData.append(key, value ? '1' : '0');
            } else if (value !== null && value !== '') {
                formData.append(key, String(value));
            }
        });

        await axios.put('/settings/company', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        toast.success('Configuración guardada');
        loadSettings();
    } catch (e: any) {
        if (e.response?.data?.errors) {
            Object.values(e.response.data.errors)
                .flat()
                .forEach((msg: string) => toast.error(msg));
        } else {
            toast.error('Error guardando configuración');
        }
    } finally {
        saving.value = false;
    }
};

const handleLogoChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    form.value.company_logo = target.files?.[0] || null;
};

onMounted(loadSettings);
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Configuración de Empresa
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Administra los datos y políticas de tu organización
                </p>
            </div>

            <div v-if="loading" class="flex justify-center py-12">
                <div
                    class="h-8 w-8 animate-spin rounded-full border-4 border-blue-600 border-t-transparent"
                ></div>
            </div>

            <form v-else @submit.prevent="saveSettings" class="space-y-6">
                <!-- Company Info -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                >
                    <h2
                        class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100"
                    >
                        Información de la Empresa
                    </h2>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="label">Nombre de la Empresa</label>
                            <input
                                v-model="form.company_name"
                                type="text"
                                class="input"
                            />
                        </div>
                        <div>
                            <label class="label">NIT / Identificación</label>
                            <input
                                v-model="form.company_identification"
                                type="text"
                                class="input"
                            />
                        </div>
                        <div>
                            <label class="label">Teléfono</label>
                            <input
                                v-model="form.company_phone"
                                type="text"
                                class="input"
                            />
                        </div>
                        <div>
                            <label class="label">Email</label>
                            <input
                                v-model="form.company_email"
                                type="email"
                                class="input"
                            />
                        </div>
                        <div class="md:col-span-2">
                            <label class="label">Dirección</label>
                            <textarea
                                v-model="form.company_address"
                                class="input"
                                rows="2"
                            ></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="label">Logo</label>
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-lg border bg-gray-100"
                                >
                                    <img
                                        v-if="logoPreview"
                                        :src="logoPreview"
                                        class="h-full w-full object-cover"
                                    />
                                    <svg
                                        v-else
                                        class="h-8 w-8 text-gray-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                        />
                                    </svg>
                                </div>
                                <input
                                    type="file"
                                    accept="image/*"
                                    @change="handleLogoChange"
                                    class="input w-auto"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vacation Policy -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                >
                    <h2
                        class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100"
                    >
                        Políticas de Vacaciones
                    </h2>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="label"
                                >Días de Vacación por Defecto</label
                            >
                            <input
                                v-model.number="form.vacation_days_default"
                                type="number"
                                min="0"
                                class="input"
                            />
                        </div>
                        <div>
                            <label class="label"
                                >Días de Anticipación Máxima</label
                            >
                            <input
                                v-model.number="form.vacation_days_advance"
                                type="number"
                                min="0"
                                class="input"
                            />
                        </div>
                        <div>
                            <label class="label"
                                >Hora de Inicio de Jornada</label
                            >
                            <input
                                v-model="form.workday_start"
                                type="time"
                                class="input"
                            />
                        </div>
                        <div>
                            <label class="label">Hora de Fin de Jornada</label>
                            <input
                                v-model="form.workday_end"
                                type="time"
                                class="input"
                            />
                        </div>
                    </div>
                </div>

                <!-- Absence Rules -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                >
                    <h2
                        class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100"
                    >
                        Reglas de Ausencias
                    </h2>

                    <div class="space-y-4">
                        <label class="flex items-center gap-3">
                            <input
                                type="checkbox"
                                v-model="form.allow_weekend_absences"
                                class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <div>
                                <span
                                    class="font-medium text-gray-900 dark:text-gray-100"
                                    >Permitir ausencias en fines de semana</span
                                >
                                <p class="text-sm text-gray-500">
                                    Los empleados pueden registrar ausencias que
                                    incluyan sábados o domingos
                                </p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3">
                            <input
                                type="checkbox"
                                v-model="form.allow_holiday_absences"
                                class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <div>
                                <span
                                    class="font-medium text-gray-900 dark:text-gray-100"
                                    >Permitir ausencias en días festivos</span
                                >
                                <p class="text-sm text-gray-500">
                                    Los empleados pueden registrar ausencias que
                                    incluyan días festivos
                                </p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3">
                            <input
                                type="checkbox"
                                v-model="form.require_approval_for_all"
                                class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <div>
                                <span
                                    class="font-medium text-gray-900 dark:text-gray-100"
                                    >Requiere aprobación para todas las
                                    ausencias</span
                                >
                                <p class="text-sm text-gray-500">
                                    Todas las ausencias deben ser aprobadas por
                                    un administrador
                                </p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3">
                            <input
                                type="checkbox"
                                v-model="form.notification_email_enabled"
                                class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <div>
                                <span
                                    class="font-medium text-gray-900 dark:text-gray-100"
                                    >Notificaciones por Email</span
                                >
                                <p class="text-sm text-gray-500">
                                    Enviar emails cuando se cree, modifique o
                                    aprueba una ausencia
                                </p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end">
                    <button
                        type="submit"
                        :disabled="saving"
                        class="btn-primary"
                    >
                        <span v-if="saving">Guardando...</span>
                        <span v-else>Guardar Configuración</span>
                    </button>
                </div>
            </form>

            <!-- Legal Links -->
            <div class="mt-8 border-t pt-6">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    <a
                        href="/legal/terms"
                        class="text-blue-600 hover:underline dark:text-blue-400"
                        >Términos y Condiciones</a
                    >
                    ·
                    <a
                        href="/legal/privacy"
                        class="text-blue-600 hover:underline dark:text-blue-400"
                        >Política de Privacidad</a
                    >
                </p>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.input {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 0.25rem;
    background-color: white;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    color: #111827;
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
    border-radius: 0.25rem;
    background-color: #2563eb;
    padding: 0.625rem 1.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: white;
}
</style>
