<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Info, Send } from 'lucide-vue-next';
import { computed } from 'vue';
import FormField from '@/components/app/FormField.vue';
import PageContainer from '@/components/app/PageContainer.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import SectionCard from '@/components/app/SectionCard.vue';
import { REQUEST_TYPES } from '@/components/requests/utils';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useNotification } from '@/composables/useNotification';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    requestTypes?: Record<string, string>;
}>();

const notify = useNotification();

const types = computed<Record<string, string>>(() =>
    props.requestTypes && Object.keys(props.requestTypes).length
        ? props.requestTypes
        : REQUEST_TYPES,
);

const form = useForm<{
    request_type: string;
    title: string;
    description: string | null;
}>({
    request_type: '',
    title: '',
    description: null,
});

const descriptionLength = computed(() => form.description?.length || 0);

const submit = () => {
    form.post('/solicitudes', {
        onSuccess: () => notify.success('Solicitud enviada a Recursos Humanos.'),
        onError: () =>
            notify.error('Revisa los campos marcados antes de enviar.'),
    });
};
</script>

<template>
    <Head title="Nueva solicitud" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Mis solicitudes', href: '/solicitudes' },
            { title: 'Nueva solicitud', href: '/solicitudes/crear' },
        ]"
    >
        <PageContainer narrow>
            <div>
                <Button as-child variant="ghost" size="sm" class="-ml-2">
                    <Link href="/solicitudes">
                        <ArrowLeft />
                        Volver a mis solicitudes
                    </Link>
                </Button>
            </div>

            <PageHeader
                title="Nueva solicitud"
                description="Completa el formulario y Recursos Humanos revisará tu solicitud."
            />

            <SectionCard>
                <form class="grid gap-5" @submit.prevent="submit">
                    <FormField
                        label="Tipo de solicitud"
                        for="req-type"
                        required
                        :error="form.errors.request_type"
                    >
                        <select
                            id="req-type"
                            v-model="form.request_type"
                            class="input"
                            required
                            :aria-invalid="!!form.errors.request_type"
                        >
                            <option value="" disabled>Selecciona un tipo…</option>
                            <option
                                v-for="(label, key) in types"
                                :key="key"
                                :value="key"
                            >
                                {{ label }}
                            </option>
                        </select>
                    </FormField>

                    <FormField
                        label="Asunto"
                        for="req-title"
                        required
                        :error="form.errors.title"
                    >
                        <Input
                            id="req-title"
                            v-model="form.title"
                            placeholder="Ej.: Certificado laboral para el banco"
                            maxlength="255"
                            required
                            :aria-invalid="!!form.errors.title"
                        />
                    </FormField>

                    <FormField
                        label="Descripción"
                        for="req-description"
                        :error="form.errors.description"
                        :hint="`Opcional · ${descriptionLength} / 1000 caracteres`"
                    >
                        <Textarea
                            id="req-description"
                            v-model="form.description"
                            rows="5"
                            maxlength="1000"
                            placeholder="Añade los detalles que ayuden a resolver tu solicitud"
                            :aria-invalid="!!form.errors.description"
                        />
                    </FormField>

                    <div
                        class="flex items-start gap-2 rounded-lg border border-info/20 bg-info-soft px-3 py-2.5 text-sm text-info"
                    >
                        <Info class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                        <p>
                            Tu solicitud será revisada por Recursos Humanos. Podrás
                            consultar su estado en «Mis solicitudes».
                        </p>
                    </div>

                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <Button as-child variant="outline">
                            <Link href="/solicitudes">Cancelar</Link>
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            <Spinner v-if="form.processing" />
                            <Send v-else />
                            {{ form.processing ? 'Enviando…' : 'Enviar solicitud' }}
                        </Button>
                    </div>
                </form>
            </SectionCard>
        </PageContainer>
    </AppLayout>
</template>
