<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { Building2, ImageIcon, Save } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import FormField from '@/components/app/FormField.vue';
import PageContainer from '@/components/app/PageContainer.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import SectionCard from '@/components/app/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useNotification } from '@/composables/useNotification';
import AppLayout from '@/layouts/AppLayout.vue';

interface CompanySettings {
    id: number;
    company_name: string;
    company_logo: string | null;
    company_logo_url?: string | null;
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

const notify = useNotification();

const loading = ref(true);
const saving = ref(false);
const settings = ref<CompanySettings | null>(null);
const selectedLogoPreview = ref<string | null>(null);
const errors = ref<Record<string, string>>({});

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

const rules = [
    {
        key: 'allow_weekend_absences',
        title: 'Permitir ausencias en fines de semana',
        description:
            'Los colaboradores pueden registrar ausencias que incluyan sábados o domingos.',
    },
    {
        key: 'allow_holiday_absences',
        title: 'Permitir ausencias en días festivos',
        description:
            'Los colaboradores pueden registrar ausencias que incluyan festivos.',
    },
    {
        key: 'require_approval_for_all',
        title: 'Requerir aprobación para todas las ausencias',
        description:
            'Cada ausencia debe ser aprobada por un administrador o jefe de área.',
    },
    {
        key: 'notification_email_enabled',
        title: 'Notificaciones por correo',
        description:
            'Enviar correos cuando se cree, modifique o apruebe una ausencia.',
    },
] as const;

const logoPreview = computed(
    () => selectedLogoPreview.value || settings.value?.company_logo_url || null,
);

const revokePreview = () => {
    if (selectedLogoPreview.value) {
        URL.revokeObjectURL(selectedLogoPreview.value);
        selectedLogoPreview.value = null;
    }
};

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
            allow_weekend_absences: !!res.data.allow_weekend_absences,
            allow_holiday_absences: !!res.data.allow_holiday_absences,
            require_approval_for_all: res.data.require_approval_for_all ?? true,
            notification_email_enabled:
                res.data.notification_email_enabled ?? true,
            company_logo: null,
        };
        revokePreview();
    } catch {
        notify.error('No se pudo cargar la configuración de la empresa.');
    } finally {
        loading.value = false;
    }
};

const saveSettings = async () => {
    saving.value = true;
    errors.value = {};
    try {
        const formData = new FormData();
        Object.entries(form.value).forEach(([key, value]) => {
            if (key === 'company_logo') {
                if (value) formData.append('company_logo', value as File);
            } else if (typeof value === 'boolean') {
                formData.append(key, value ? '1' : '0');
            } else if (value !== null && value !== '') {
                formData.append(key, String(value));
            }
        });
        // PHP no interpreta cuerpos multipart en PUT: se usa POST + _method.
        formData.append('_method', 'PUT');

        await axios.post('/settings/company', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        notify.success('Configuración guardada.');
        await loadSettings();
    } catch (e: any) {
        const errs = e?.response?.data?.errors;
        if (errs) {
            errors.value = Object.fromEntries(
                Object.entries(errs).map(([k, v]) => [
                    k,
                    Array.isArray(v) ? String(v[0]) : String(v),
                ]),
            );
            notify.error('Revisa los campos marcados.');
        } else {
            notify.error('No se pudo guardar la configuración.');
        }
    } finally {
        saving.value = false;
    }
};

const handleLogoChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0] || null;
    form.value.company_logo = file;
    revokePreview();
    if (file) selectedLogoPreview.value = URL.createObjectURL(file);
};

onMounted(loadSettings);
onBeforeUnmount(revokePreview);
</script>

<template>
    <Head title="Configuración de la empresa" />
    <AppLayout>
        <PageContainer narrow>
            <PageHeader
                title="Configuración de la empresa"
                description="Datos de la organización y políticas que aplican a ausencias y vacaciones."
                :icon="Building2"
            >
                <template #actions>
                    <Button
                        type="submit"
                        form="company-settings-form"
                        :disabled="saving || loading"
                    >
                        <Spinner v-if="saving" />
                        <Save v-else />
                        {{ saving ? 'Guardando…' : 'Guardar cambios' }}
                    </Button>
                </template>
            </PageHeader>

            <div v-if="loading" class="grid gap-4">
                <Skeleton class="h-72 rounded-xl" />
                <Skeleton class="h-40 rounded-xl" />
                <Skeleton class="h-56 rounded-xl" />
            </div>

            <form
                v-else
                id="company-settings-form"
                class="grid gap-6"
                novalidate
                @submit.prevent="saveSettings"
            >
                <SectionCard
                    title="Información de la empresa"
                    description="Aparece en documentos, correos y en el menú lateral."
                >
                    <div class="grid gap-4 md:grid-cols-2">
                        <FormField
                            label="Nombre de la empresa"
                            for="company_name"
                            :error="errors.company_name"
                        >
                            <Input
                                id="company_name"
                                v-model="form.company_name"
                                autocomplete="organization"
                                :aria-invalid="!!errors.company_name"
                            />
                        </FormField>
                        <FormField
                            label="NIT / identificación"
                            for="company_identification"
                            :error="errors.company_identification"
                        >
                            <Input
                                id="company_identification"
                                v-model="form.company_identification"
                                :aria-invalid="!!errors.company_identification"
                            />
                        </FormField>
                        <FormField
                            label="Teléfono"
                            for="company_phone"
                            :error="errors.company_phone"
                        >
                            <Input
                                id="company_phone"
                                v-model="form.company_phone"
                                type="tel"
                                autocomplete="tel"
                                :aria-invalid="!!errors.company_phone"
                            />
                        </FormField>
                        <FormField
                            label="Correo electrónico"
                            for="company_email"
                            :error="errors.company_email"
                        >
                            <Input
                                id="company_email"
                                v-model="form.company_email"
                                type="email"
                                autocomplete="email"
                                :aria-invalid="!!errors.company_email"
                            />
                        </FormField>
                        <FormField
                            class="md:col-span-2"
                            label="Dirección"
                            for="company_address"
                            :error="errors.company_address"
                        >
                            <Textarea
                                id="company_address"
                                v-model="form.company_address"
                                rows="2"
                                :aria-invalid="!!errors.company_address"
                            />
                        </FormField>
                        <FormField
                            class="md:col-span-2"
                            label="Logo"
                            for="company_logo"
                            :error="errors.company_logo"
                            hint="PNG, JPG o SVG. La vista previa se actualiza al seleccionar el archivo."
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-lg border bg-muted"
                                >
                                    <img
                                        v-if="logoPreview"
                                        :src="logoPreview"
                                        alt="Vista previa del logo"
                                        class="size-full object-contain p-1"
                                    />
                                    <ImageIcon
                                        v-else
                                        class="size-6 text-muted-foreground"
                                        aria-hidden="true"
                                    />
                                </div>
                                <Input
                                    id="company_logo"
                                    type="file"
                                    accept="image/*"
                                    class="cursor-pointer"
                                    @change="handleLogoChange"
                                />
                            </div>
                        </FormField>
                    </div>
                </SectionCard>

                <SectionCard
                    title="Política de vacaciones y jornada"
                    description="Valores por defecto para nuevos colaboradores."
                >
                    <div class="grid gap-4 sm:grid-cols-2">
                        <FormField
                            label="Días de vacaciones por defecto"
                            for="vacation_days_default"
                            :error="errors.vacation_days_default"
                        >
                            <Input
                                id="vacation_days_default"
                                v-model.number="form.vacation_days_default"
                                type="number"
                                min="0"
                            />
                        </FormField>
                        <FormField
                            label="Días de anticipación máxima"
                            for="vacation_days_advance"
                            :error="errors.vacation_days_advance"
                        >
                            <Input
                                id="vacation_days_advance"
                                v-model.number="form.vacation_days_advance"
                                type="number"
                                min="0"
                            />
                        </FormField>
                        <FormField
                            label="Inicio de jornada"
                            for="workday_start"
                            :error="errors.workday_start"
                        >
                            <Input
                                id="workday_start"
                                v-model="form.workday_start"
                                type="time"
                            />
                        </FormField>
                        <FormField
                            label="Fin de jornada"
                            for="workday_end"
                            :error="errors.workday_end"
                        >
                            <Input
                                id="workday_end"
                                v-model="form.workday_end"
                                type="time"
                            />
                        </FormField>
                    </div>
                </SectionCard>

                <SectionCard title="Reglas de ausencias" flush>
                    <ul class="divide-y">
                        <li v-for="rule in rules" :key="rule.key">
                            <label
                                :for="`rule-${rule.key}`"
                                class="flex cursor-pointer items-start gap-3 px-4 py-3.5 hover:bg-muted/40 sm:px-5"
                            >
                                <input
                                    :id="`rule-${rule.key}`"
                                    v-model="form[rule.key]"
                                    type="checkbox"
                                    class="mt-0.5 size-4 shrink-0 accent-primary"
                                />
                                <span class="grid gap-0.5">
                                    <span class="text-sm font-medium">{{
                                        rule.title
                                    }}</span>
                                    <span
                                        class="text-[13px] text-muted-foreground"
                                        >{{ rule.description }}</span
                                    >
                                </span>
                            </label>
                        </li>
                    </ul>
                </SectionCard>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="saving">
                        <Spinner v-if="saving" />
                        {{ saving ? 'Guardando…' : 'Guardar cambios' }}
                    </Button>
                </div>
            </form>

            <nav
                aria-label="Documentos legales"
                class="flex flex-wrap gap-x-4 gap-y-1 border-t pt-4 text-[13px] text-muted-foreground"
            >
                <a href="/legal/terms" class="hover:text-foreground hover:underline"
                    >Términos y condiciones</a
                >
                <a
                    href="/legal/privacy"
                    class="hover:text-foreground hover:underline"
                    >Política de privacidad</a
                >
                <a
                    href="/legal/cookies"
                    class="hover:text-foreground hover:underline"
                    >Política de cookies</a
                >
                <a
                    href="/legal/refunds"
                    class="hover:text-foreground hover:underline"
                    >Reembolsos y retracto</a
                >
            </nav>
        </PageContainer>
    </AppLayout>
</template>
