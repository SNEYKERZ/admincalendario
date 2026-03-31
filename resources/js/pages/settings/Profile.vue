<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { getInitials } from '@/composables/useInitials';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { BreadcrumbItem } from '@/types';

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
};

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Mi perfil',
        href: edit(),
    },
];

const page = usePage();
const user = computed(() => page.props.auth.user);
const avatarPreview = ref<string | null>(user.value.avatar ?? user.value.photo_url ?? null);

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
    const months = (now.getFullYear() - start.getFullYear()) * 12 + (now.getMonth() - start.getMonth());

    if (months < 12) {
        return `${Math.max(months, 0)} mes(es) en la empresa`;
    }

    const years = Math.floor(months / 12);
    const remainingMonths = months % 12;

    return `${years} año(s) y ${remainingMonths} mes(es) en la empresa`;
});

const handlePhotoChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;

    form.photo = file;
    avatarPreview.value = file ? URL.createObjectURL(file) : (user.value.avatar ?? user.value.photo_url ?? null);
};

const submit = () => {
    form
        .transform((data) => ({
            ...data,
            name: `${data.first_name} ${data.last_name}`.trim(),
            _method: 'patch',
        }))
        .post('/settings/profile', {
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
                    description="Actualiza tu foto, tus datos personales y tu informacion laboral"
                />

                <form class="space-y-6" @submit.prevent="submit">
                    <div class="rounded-2xl border border-gray-200 p-5 dark:border-gray-800">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                            <Avatar class="h-24 w-24 rounded-2xl">
                                <AvatarImage v-if="avatarPreview" :src="avatarPreview" :alt="user.name" />
                                <AvatarFallback class="rounded-2xl text-xl">
                                    {{ getInitials(user.name) }}
                                </AvatarFallback>
                            </Avatar>

                            <div class="flex-1 space-y-2">
                                <div>
                                    <Label for="photo">Foto de perfil</Label>
                                    <Input id="photo" type="file" accept="image/*" class="mt-2" @change="handlePhotoChange" />
                                    <InputError class="mt-2" :message="form.errors.photo" />
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    Usa una imagen clara. Tamaño máximo: 2 MB.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="first_name">Nombres</Label>
                            <Input id="first_name" v-model="form.first_name" placeholder="Nombres" />
                            <InputError :message="form.errors.first_name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="last_name">Apellidos</Label>
                            <Input id="last_name" v-model="form.last_name" placeholder="Apellidos" />
                            <InputError :message="form.errors.last_name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="identification">Identificacion</Label>
                            <Input id="identification" v-model="form.identification" placeholder="Numero de identificacion" />
                            <InputError :message="form.errors.identification" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="phone">Numero de celular</Label>
                            <Input id="phone" v-model="form.phone" placeholder="Celular" />
                            <InputError :message="form.errors.phone" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="email">Email</Label>
                            <Input id="email" v-model="form.email" type="email" placeholder="Email" />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="birth_date">Fecha de nacimiento</Label>
                            <Input id="birth_date" v-model="form.birth_date" type="date" />
                            <InputError :message="form.errors.birth_date" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="hire_date">Fecha de ingreso a la empresa</Label>
                            <Input id="hire_date" v-model="form.hire_date" type="date" />
                            <InputError :message="form.errors.hire_date" />
                            <p class="text-xs text-muted-foreground">{{ tenureLabel }}</p>
                        </div>
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="text-sm text-muted-foreground">
                            Tu correo aun no esta verificado.
                            <Link
                                :href="send()"
                                as="button"
                                class="underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current dark:decoration-neutral-500"
                            >
                                Reenviar correo de verificacion.
                            </Link>
                        </p>

                        <div
                            v-if="status === 'verification-link-sent'"
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            Se envio un nuevo enlace de verificacion a tu correo.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing" data-test="update-profile-button">
                            Guardar perfil
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">
                                Guardado.
                            </p>
                        </Transition>
                    </div>
                </form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>

