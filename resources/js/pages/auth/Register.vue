<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';
</script>

<template>
    <AuthBase
        title="Crear una cuenta"
        description="Ingresa tus datos para crear la cuenta"
    >
        <Head title="Registro" />

        <Form
            v-bind="store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">Nombre completo</Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        autocomplete="name"
                        name="name"
                        placeholder="Nombre y apellidos"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Correo electrónico</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        autocomplete="email"
                        name="email"
                        placeholder="correo@ejemplo.com"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Contraseña</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        autocomplete="new-password"
                        name="password"
                        placeholder="Contraseña"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirmar contraseña</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        name="password_confirmation"
                        placeholder="Repita la contraseña"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <div class="grid gap-2">
                    <Label
                        for="accept_legal"
                        class="flex items-start gap-3 text-sm leading-snug font-normal"
                    >
                        <Checkbox
                            id="accept_legal"
                            name="accept_legal"
                            required
                            aria-describedby="accept_legal_error"
                            class="mt-0.5"
                        />
                        <span>
                            He leído y acepto los
                            <a
                                href="/legal/terms"
                                target="_blank"
                                rel="noopener"
                                class="underline underline-offset-4"
                                >Términos y condiciones</a
                            >
                            y autorizo el tratamiento de mis datos personales
                            según la
                            <a
                                href="/legal/privacy"
                                target="_blank"
                                rel="noopener"
                                class="underline underline-offset-4"
                                >Política de tratamiento de datos</a
                            >.
                        </span>
                    </Label>
                    <InputError
                        id="accept_legal_error"
                        :message="errors.accept_legal"
                    />
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full"
                    :disabled="processing"
                    data-test="register-user-button"
                >
                    <Spinner v-if="processing" />
                    Crear cuenta
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                ¿Ya tienes una cuenta?
                <TextLink
                    :href="login()"
                    class="underline underline-offset-4"
                    >Iniciar sesión</TextLink
                >
            </div>
        </Form>
    </AuthBase>
</template>

