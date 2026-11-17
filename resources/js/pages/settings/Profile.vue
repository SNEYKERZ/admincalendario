<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Check } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { getInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { BreadcrumbItem } from '@/types';

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
};

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Mi perfil',
        href: edit(),
    },
];

const page = usePage();
const user = computed(() => page.props.auth.user as any);
const avatarPreview = ref<string | null>(
    user.value.avatar ?? user.value.photo_url ?? null,
);

const form = useForm({
    first_name: user.value.first_name ?? user.value.name?.split(' ')[0] ?? '',
    last_name: user.value.last_name ?? '',
    identification: user.value.identification ?? '',
    phone: user.value.phone ?? '',
    email: user.value.email ?? '',
    birth_date: user.value.birth_date ?? '',
    hire_date: user.value.hire_date ?? '',
    photo: null as File | null,
});

const tenureLabel = computed(() => {
    if (!form.hire_date) return 'Sin fecha de ingreso registrada';

    const start = new Date(form.hire_date);
    const now = new Date();
    const months =
        (now.getFullYear() - start.getFullYear()) * 12 +
        (now.getMonth() - start.getMonth());

    if (months < 12) {
        const m = Math.max(months, 0);
        return `${m} ${m === 1 ? 'mes' : 'meses'} en la empresa`;
    }

    const years = Math.floor(months / 12);
    const remainingMonths = months % 12;

    return `${years} ${years === 1 ? 'año' : 'años'} y ${remainingMonths} ${remainingMonths === 1 ? 'mes' : 'meses'} en la empresa`;
});

const handlePhotoChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;

    form.photo = file;
    avatarPreview.value = file
        ? URL.createObjectURL(file)
        : (user.value.avatar ?? user.value.photo_url ?? null);
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        name: `${data.first_name} ${data.last_name}`.trim(),
        _method: 'patch',
    })).post('/settings/profile', {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Mi perfil" />

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="Mi perfil"
                    description="Actualiza tu foto, tus datos personales y tu información laboral."
                />

                <form class="space-y-6" @submit.prevent="submit">
                    <div class="rounded-xl border bg-card p-4">
                        <div
                            class="flex flex-col gap-4 sm:flex-row sm:items-center"
                        >
                            <Avatar class="size-20 rounded-xl">
                                <AvatarImage
                                    v-if="avatarPreview"
                                    :src="avatarPreview"
                                    :alt="user.name"
                                />
                                <AvatarFallback class="rounded-xl text-xl">
                                    {{ getInitials(user.name) }}
                                </AvatarFallback>
                            </Avatar>

                            <div class="flex-1 space-y-1.5">
                                <Label for="photo">Foto de perfil</Label>
                                <Input
                                    id="photo"
                                    type="file"
                                    accept="image/*"
                                    @change="handlePhotoChange"
                                />
                                <InputError :message="form.errors.photo" />
                                <p class="text-xs text-muted-foreground">
                                    Usa una imagen clara. Tamaño máximo: 2 MB.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label for="first_name">Nombres</Label>
                            <Input
                                id="first_name"
                                v-model="form.first_name"
                                autocomplete="given-name"
                                :aria-invalid="!!form.errors.first_name"
                            />
                            <InputError :message="form.errors.first_name" />
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="last_name">Apellidos</Label>
                            <Input
                                id="last_name"
                                v-model="form.last_name"
                                autocomplete="family-name"
                                :aria-invalid="!!form.errors.last_name"
                            />
                            <InputError :message="form.errors.last_name" />
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="identification">Identificación</Label>
                            <Input
                                id="identification"
                                v-model="form.identification"
                                placeholder="Número de identificación"
                                :aria-invalid="!!form.errors.identification"
                            />
                            <InputError :message="form.errors.identification" />
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="phone">Número de celular</Label>
                            <Input
                                id="phone"
                                v-model="form.phone"
                                type="tel"
                                autocomplete="tel"
                                :aria-invalid="!!form.errors.phone"
                            />
                            <InputError :message="form.errors.phone" />
                        </div>

                        <div class="grid gap-1.5 md:col-span-2">
                            <Label for="email">Correo electrónico</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                autocomplete="email"
                                :aria-invalid="!!form.errors.email"
                            />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="birth_date">Fecha de nacimiento</Label>
                            <Input
                                id="birth_date"
                                v-model="form.birth_date"
                                type="date"
                            />
                            <InputError :message="form.errors.birth_date" />
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="hire_date"
                                >Fecha de ingreso a la empresa</Label
                            >
                            <Input
                                id="hire_date"
                                v-model="form.hire_date"
                                type="date"
                            />
                            <InputError :message="form.errors.hire_date" />
                            <p class="text-xs text-muted-foreground">
                                {{ tenureLabel }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="mustVerifyEmail && !user.email_verified_at"
                        class="rounded-lg border border-warning/25 bg-warning-soft p-3 text-sm text-warning"
                    >
                        <p>
                            Tu correo aún no está verificado.
                            <Link
                                :href="send()"
                                as="button"
                                class="font-medium underline underline-offset-4"
                            >
                                Reenviar correo de verificación
                            </Link>
                        </p>

                        <p
                            v-if="status === 'verification-link-sent'"
                            class="mt-2 font-medium text-success"
                        >
                            Se envió un nuevo enlace de verificación a tu
                            correo.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <Button
                            :disabled="form.processing"
                            data-test="update-profile-button"
                        >
                            <Spinner v-if="form.processing" />
                            {{
                                form.processing
                                    ? 'Guardando…'
                                    : 'Guardar perfil'
                            }}
                        </Button>

                        <Transition
                            enter-active-class="transition ease-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="form.recentlySuccessful"
                                class="flex items-center gap-1 text-sm text-success"
                                role="status"
                            >
                                <Check class="size-4" /> Guardado
                            </p>
                        </Transition>
                    </div>
                </form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
